(function ($, Drupal) {
  Drupal.behaviors.yandexMaps = {
    attach: function (context, settings) {
      if (window.ymaps) {
        // Fix bug at dynamic load library in ajax request
        if (!(context instanceof HTMLElement)) {
          context = document;
        }

        var maps = once('yandex-map', 'div.yandex-map', context);
        if (maps.length) {
          ymaps.ready(function () {
            maps.forEach(function (mapElement) {
              if (!mapElement.id) {
                mapElement.id = Drupal.yandexMaps.generateMapId();
              }
              Drupal.yandexMaps.mapInit(mapElement.id);
            });
          });
        }
      }
    }
  };

  // Trigger DOM event after ymaps ready.
  $(function () {
    ymaps.ready(function () {
      $(document).trigger('ymapsReady');
    });
  });

  Drupal.yandexMaps = Drupal.yandexMaps || {
    data: {},

    defaultSettings: {
      center: [0, 0],
      zoom: 0
    },

    /**
     * Initialize map.
     */
    mapInit: function (mapId, mapSettings) {
      var dataSettings = Drupal.yandexMaps.getDataSettings(mapId);
      mapSettings = $.extend({}, Drupal.yandexMaps.defaultSettings, dataSettings, mapSettings);

      if (!drupalSettings.yandexMaps) {
        drupalSettings.yandexMaps = {};
      }

      // Create map
      var map = Drupal.yandexMaps.createMap(mapId, mapSettings);

      // Set geo objects default options
      var defaultObjectsOptions = {strokeWidth: 4};
      if (drupalSettings.yandexMaps.objectsDefaultPreset) {
        defaultObjectsOptions.preset = drupalSettings.yandexMaps.objectsDefaultPreset;
      }
      if (mapSettings.objectPreset) {
        defaultObjectsOptions.preset = mapSettings.objectPreset;
      }
      map.geoObjects.options.set(defaultObjectsOptions);

      // Add geo objects
      if (!mapSettings.withoutObjects) {
        if (mapSettings.objects) {
          Drupal.yandexMaps.addObject(map, mapSettings.objects, mapSettings.editable, mapSettings.clusterize);
        }
        else if (mapSettings.editable) {
          var objects = $('#' + mapId + ' ~ input[name$="[objects]"]').val();
          Drupal.yandexMaps.addObject(map, objects, mapSettings.editable);
        }
      }

      // Restore map state from cookie
      if (mapSettings.saveState && Drupal.yandexMaps.getMapStateFromCookie(mapId)) {
        var mapState = Drupal.yandexMaps.getMapStateFromCookie(mapId);
        map.setCenter(mapState.center);
        map.setZoom(mapState.zoom);
      }
      // Auto centering and zooming
      else {
        // Auto centering
        if (mapSettings.autoCentering || !mapSettings.center || (mapSettings.center[0] == 0 && mapSettings.center[1] == 0)) {
          Drupal.yandexMaps.autoCentering(map);
        }

        // Auto zooming
        if (mapSettings.autoZooming || !mapSettings.zoom) {
          Drupal.yandexMaps.autoZooming(map);
        }
      }

      // Editing features
      if (mapSettings.editable) {
        Drupal.yandexMaps.addEditButtons(map, mapSettings.objectTypes);
        map.geoObjects.events.add('remove', Drupal.yandexMaps.objectsChangeHandler);
        map.events.add('click', Drupal.yandexMaps.mapClickHandler);

        // Select default edit button
        if (mapSettings.selectedControl) {
          var edutButton = Drupal.yandexMaps.getEditButton(map, mapSettings.selectedControl);
          edutButton.events.fire('click');
          edutButton.select();
        }
      }

      if (mapSettings.editable || mapSettings.saveState) {
        map.events.add('boundschange', Drupal.yandexMaps.mapBoundschangeHandler);
      }

      Drupal.yandexMaps.tweakSearchControl(map);

      $('#' + mapId).trigger('ymapsCreated', [map]);

      return map;
    },

    /**
     * Create map.
     */
    createMap: function (mapId, settings) {
      var mapState = Drupal.yandexMaps.getMapStateFromSettings(settings);
      var map = new ymaps.Map(mapId, mapState, settings.options);
      map.mapId = mapId;

      Drupal.yandexMaps.data[mapId] = {
        map: map,
        settings: settings,
        cursor: null,
        drawingMode: false
      };

      return map;
    },

    /**
     * Return map data-* settings.
     */
    getDataSettings: function (mapId) {
      var $map = $('#' + mapId);
      var settings = {};
      var arrayTypeSettings = ['center', 'controls', 'behaviors', 'objectTypes'];

      $.each($map.data(), function (attributeKey, attributeValue) {
        var matches = attributeKey.match(/map(.+)/);
        if (matches) {
          var settingKey = matches[1].substring(0, 1).toLowerCase() + matches[1].substring(1);

          if ($.inArray(settingKey, arrayTypeSettings) != -1 && $.type(attributeValue) != 'array') {
            attributeValue = (attributeValue == '<none>') ? [] : attributeValue.split(',');
            attributeValue = $.map(attributeValue, $.trim);
          }
          // Boolean setting without value, example <div data-map-clusterize>
          else if (attributeValue === '') {
            attributeValue = true;
          }

          settings[settingKey] = attributeValue;
        }
      });

      return settings;
    },

    /**
     * Return default map state from settings.
     */
    getMapStateFromSettings: function (settings) {
      var state = {};
      $.each(['behaviors', 'bounds', 'center', 'controls', 'type', 'zoom'], function (index, stateKey) {
        if (stateKey in settings) {
          state[stateKey] = settings[stateKey];
        }
      });
      return state;
    },

    /**
     * Return map state from cookie.
     */
    getMapStateFromCookie: function (mapId) {
      if (!$.cookie) {
        return;
      }

      var mapsState = $.cookie('Drupal.visitor.mapsState');
      mapsState = mapsState ? JSON.parse(mapsState) : {};

      if (!mapId) {
        return mapsState;
      }
      else if (mapsState[mapId]) {
        return mapsState[mapId];
      }
      return;
    },

    /**
     * Save map state to cookie.
     */
    saveMapStateToCookie: function (mapId, mapState) {
      if (!$.cookie) {
        return;
      }

      var mapsState = Drupal.yandexMaps.getMapStateFromCookie();
      mapsState[mapId] = mapState;
      $.cookie('Drupal.visitor.mapsState', JSON.stringify(mapsState), {
        path: drupalSettings.path.baseUrl,
        expires: 365
      });
    },

    /**
     * Add edit buttons.
     */
    addEditButtons: function (map, buttonTypes) {
      if (!buttonTypes) return;

      var buttonTitles = {
        point:   Drupal.t('Add point', {}, {context: 'Geometry'}),
        line:    Drupal.t('Add line', {}, {context: 'Geometry'}),
        polygon: Drupal.t('Add polygone', {}, {context: 'Geometry'}),
      };

      var i = 0;
      $.each(buttonTypes, function (index, buttonType) {
        var button = new ymaps.control.Button({
          data: {
            image: Drupal.url(drupalSettings.yandexMaps.modulePath) + '/img/icon-' + buttonType + '.svg',
            title: buttonTitles[buttonType],
            editButtonType: buttonType // Custom data property
          }
        });
        button.events.add('click', Drupal.yandexMaps.editButtonClickHandler);
        map.controls.add(button, {floatIndex: i--});
      });
    },

    /**
     * Return selected edit button.
     */
    getSelectedEditButton: function (map) {
      var button = null;
      map.controls.each(function (control) {
        if (control.data.get('editButtonType') && control.state.get('selected')) {
          button = control;
        }
      });
      return button;
    },

    /**
     * Return edit button by type.
     */
    getEditButton: function (map, editButtonType) {
      var editButton;
      map.controls.each(function (control) {
        if (control.data.get('editButtonType') == editButtonType) {
          editButton = control;
        }
      });
      return editButton;
    },

    /**
     * Deselect controls.
     */
    deselectControls: function (map) {
      // Deselect edit buttons
      map.controls.each(function (control) {
        if (control.data.get('editButtonType')) {
          control.deselect();
        }
      });

      // Deselect ruler
      var rulerControl = map.controls.get('rulerControl');
      if (rulerControl) {
        rulerControl.deselect();
      }
    },

    /**
     * Add geo object.
     */
    addObject: function (map, object, editMode, clusterize) {
      if (!object) return;

      if ($.type(object) === 'string')  {
        object = JSON.parse(object);
      }

      var geoQueryResult = ymaps.geoQuery(object);

      // Clusterize placemarks
      if (clusterize) {
        var points = geoQueryResult.search('geometry.type = "Point"');
        var notPoints = geoQueryResult.search('geometry.type != "Point"');
        var clusterer = points.clusterize({
          hasHint: false,
          margin: 15,
          zoomMargin: 20
        });
        map.geoObjects.add(clusterer);
        notPoints.addToMap(map);
      }
      else {
        geoQueryResult.addToMap(map);
      }

      // Enable edit mode
      if (editMode) {
        geoQueryResult.addEvents(['mapchange', 'editorstatechange', 'dragend', 'geometrychange'], Drupal.yandexMaps.objectsChangeHandler);
        geoQueryResult.addEvents('editorstatechange', Drupal.yandexMaps.objectEditorStateChangeHandler);
        geoQueryResult.addEvents('dblclick', Drupal.yandexMaps.objectsDblclickHandler);
        geoQueryResult.setOptions({draggable: true});
        geoQueryResult.each(function (object) {
          object.editor.startEditing();
        });
      }
    },

    /**
     * Add geo object by type.
     */
    addObjectByType: function (map, objectType, geometry, editMode, startDrawing) {
      var object;

      if (objectType == 'point') {
        object = new ymaps.Placemark(geometry);
      }
      else if (objectType == 'line') {
        object = new ymaps.Polyline(geometry);
      }
      else if (objectType == 'polygon') {
        object = new ymaps.Polygon(geometry);
      }

      Drupal.yandexMaps.addObject(map, object, editMode);

      if (startDrawing) {
        object.editor.startDrawing();
      }
    },

    /**
     * Return map.geoObjects in GeoJSON format.
     */
    getObjectsInGeoJson: function (map) {
      var objects = {
        type: 'FeatureCollection',
        features: []
      };
      map.geoObjects.each(function (object) {
        var feature = {
          type: 'Feature',
          geometry: {
            type: object.geometry.getType(),
            coordinates: object.geometry.getCoordinates()
          }
        };
        objects.features.push(feature);
      });
      return JSON.stringify(objects);
    },

    /**
     * Auto centering map.
     */
    autoCentering: function (map) {
      if (map.geoObjects.getLength() == 0) {
        return;
      }

      var centerAndZoom = ymaps.util.bounds.getCenterAndZoom(map.geoObjects.getBounds(), map.container.getSize());
      map.setCenter(centerAndZoom.center);
    },

    /**
     * Auto zooming map.
     */
    autoZooming: function (map) {
      if (map.geoObjects.getLength() == 0) {
        return;
      }

      var mapSize = map.container.getSize();
      var centerAndZoom = ymaps.util.bounds.getCenterAndZoom(
        map.geoObjects.getBounds(),
        mapSize,
        ymaps.projection.wgs84Mercator,
        {margin: 40}
      );
      map.setZoom(centerAndZoom.zoom <= 16 ? centerAndZoom.zoom : 16);
    },

    /**
     * Return new map id.
     */
    generateMapId: function (number) {
      if (!number) number = 1;
      var mapId = 'yandex-map-' + number;

      if ($('#' + mapId).length > 0) {
        return Drupal.yandexMaps.generateMapId(number + 1);
      }

      return mapId;
    },

    /**
     * Return total bounds by bounds collection.
     */
    getTotalBounds: function (boundsCollection) {
      var totalBounds;

      $.each(boundsCollection, function (index, bounds) {
        if (!bounds) {
          return;
        }
        if (!totalBounds) {
          totalBounds = bounds;
          return;
        }

        // Min
        if (totalBounds[0][0] > bounds[0][0]) totalBounds[0][0] = bounds[0][0];
        if (totalBounds[0][1] > bounds[0][1]) totalBounds[0][1] = bounds[0][1];
        // Max
        if (totalBounds[1][0] < bounds[1][0]) totalBounds[1][0] = bounds[1][0];
        if (totalBounds[1][1] < bounds[1][1]) totalBounds[1][1] = bounds[1][1];
      });

      return totalBounds;
    },

    /**
     * Tweak search control.
     */
    tweakSearchControl: function (map) {
      var searchControl = map.controls.get('searchControl');
      if (searchControl) {
        // Remove search result placemark after close balloon
        searchControl.events.add('resultshow', function (event) {
          var resultIndex = event.get('index');
          var result = searchControl.getResultsArray()[resultIndex];
          result.balloon.events.add('close', function (event) {
            searchControl.hideResult();
          });
        });
      }
    },

    /**
     * Change map cursor.
     */
    changeCursor: function (map, cursor) {
      var cursorAccessor = Drupal.yandexMaps.data[map.mapId].cursor;
      if (!cursorAccessor || !cursorAccessor.getKey(cursor)) {
        cursorAccessor = map.cursors.push(cursor);
      }
      else {
        cursorAccessor.setKey(cursor);
      }
    },

    /**
     * Edit button click handler.
     */
    editButtonClickHandler: function (event) {
      var button = event.get('target');
      var map = button.getMap();

      if (!button.state.get('selected')) {
        Drupal.yandexMaps.deselectControls(map);
        Drupal.yandexMaps.changeCursor(map, 'arrow');
      }
      else {
        Drupal.yandexMaps.changeCursor(map, 'grab');
      }
    },

    /**
     * Object editorstatechange handler.
     */
    objectEditorStateChangeHandler: function (event) {
      var geoObject = event.get('target');
      var map = geoObject.getMap();
      if (map) {
        Drupal.yandexMaps.data[map.mapId].drawingMode = geoObject.editor.state.get('drawing');
      }
    },

    /**
     * Objects change handler.
     */
    objectsChangeHandler: function (event) {
      var geoObject = event.get('target');
      var map = geoObject.getMap();
      if (map) {
        var $objectsInput = $('#' + map.mapId + ' ~ input[name$="[objects]"]');
        $objectsInput.val(Drupal.yandexMaps.getObjectsInGeoJson(map));
      }
    },

    /**
     * Object dblclick handler.
     */
    objectsDblclickHandler: function (event) {
      var geoObject = event.get('target');
      var map = geoObject.getMap();
      map.geoObjects.remove(geoObject);
      event.stopPropagation();
    },

    /**
     * Map click handler.
     */
    mapClickHandler: function (event) {
      var map = event.get('target');
      var settings = Drupal.yandexMaps.data[map.mapId].settings;
      var selectedButton = Drupal.yandexMaps.getSelectedEditButton(map);

      if (selectedButton && !Drupal.yandexMaps.data[map.mapId].drawingMode) {
        if (!settings.multiple) {
          map.geoObjects.removeAll();
        }

        var selectedButtonType = selectedButton.data.get('editButtonType');
        var geometry = event.get('coords');
        var startDrawing = false;

        if (selectedButtonType == 'line') {
          geometry = [geometry];
          startDrawing = true;
        }
        else if (selectedButtonType == 'polygon') {
          geometry = [[geometry]];
          startDrawing = true;
        }

        Drupal.yandexMaps.addObjectByType(map, selectedButtonType, geometry, true, startDrawing);
      }
    },

    /**
     * Map boundschange handler.
     */
    mapBoundschangeHandler: function (event) {
      var map = event.get('target');
      if (map) {
        var settings = Drupal.yandexMaps.data[map.mapId].settings;

        if (settings.editable) {
          $('#' + map.mapId + ' ~ input[name$="[center]"]').val(map.getCenter().join(','));
          $('#' + map.mapId + ' ~ input[name$="[zoom]"]').val(map.getZoom());
        }

        if (settings.saveState) {
          Drupal.yandexMaps.saveMapStateToCookie(map.mapId, {
            center: map.getCenter(),
            zoom: map.getZoom(),
          });
        }
      }
    }
  };
})(jQuery, Drupal);
