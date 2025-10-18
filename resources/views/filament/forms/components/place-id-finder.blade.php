@php
    $apiKey = config('google.maps_api_key');
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @if(!$apiKey)
        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-yellow-800 dark:text-yellow-300 mb-1">
                        Google Maps API Key erforderlich
                    </h4>
                    <p class="text-xs text-yellow-700 dark:text-yellow-400">
                        Bitte fügen Sie <code class="px-1 py-0.5 bg-yellow-100 dark:bg-yellow-800 rounded">GOOGLE_MAPS_API_KEY</code>
                        zu Ihrer <code class="px-1 py-0.5 bg-yellow-100 dark:bg-yellow-800 rounded">.env</code> Datei hinzu,
                        um den Place ID Finder zu nutzen.
                    </p>
                </div>
            </div>
        </div>
    @else
        <div
            x-data="{
                map: null,
                marker: null,
                autocomplete: null,
                infowindow: null,
                selectedPlace: null,
                showFinder: false,

                init() {
                    // Wait for Google Maps to load
                    this.$nextTick(() => {
                        if (typeof google !== 'undefined') {
                            this.initMap();
                        } else {
                            console.error('Google Maps not loaded');
                        }
                    });
                },

                initMap() {
                    const mapElement = this.$refs.mapContainer;

                    // Default to Berlin
                    const defaultCenter = { lat: 52.520008, lng: 13.404954 };

                    this.map = new google.maps.Map(mapElement, {
                        center: defaultCenter,
                        zoom: 13,
                        mapTypeControl: true,
                    });

                    this.infowindow = new google.maps.InfoWindow();

                    // Setup autocomplete
                    const input = this.$refs.autocompleteInput;
                    this.autocomplete = new google.maps.places.Autocomplete(input);
                    this.autocomplete.bindTo('bounds', this.map);
                    this.autocomplete.setFields(['place_id', 'geometry', 'name', 'formatted_address']);

                    this.autocomplete.addListener('place_changed', () => {
                        this.handlePlaceSelect();
                    });

                    // Allow clicking on map
                    this.map.addListener('click', (e) => {
                        this.handleMapClick(e.latLng);
                    });
                },

                handlePlaceSelect() {
                    this.infowindow.close();

                    const place = this.autocomplete.getPlace();

                    if (!place.geometry || !place.geometry.location) {
                        return;
                    }

                    this.selectedPlace = {
                        placeId: place.place_id,
                        name: place.name,
                        address: place.formatted_address
                    };

                    // Update map
                    if (place.geometry.viewport) {
                        this.map.fitBounds(place.geometry.viewport);
                    } else {
                        this.map.setCenter(place.geometry.location);
                        this.map.setZoom(17);
                    }

                    // Add marker
                    if (this.marker) {
                        this.marker.setMap(null);
                    }

                    this.marker = new google.maps.Marker({
                        map: this.map,
                        position: place.geometry.location,
                    });

                    this.showInfoWindow(place);
                },

                handleMapClick(location) {
                    const geocoder = new google.maps.Geocoder();

                    geocoder.geocode({ location: location }, (results, status) => {
                        if (status === 'OK' && results[0]) {
                            this.selectedPlace = {
                                placeId: results[0].place_id,
                                name: results[0].name || 'Unbekannt',
                                address: results[0].formatted_address
                            };

                            if (this.marker) {
                                this.marker.setMap(null);
                            }

                            this.marker = new google.maps.Marker({
                                map: this.map,
                                position: location,
                            });

                            this.showInfoWindow(results[0]);
                        }
                    });
                },

                showInfoWindow(place) {
                    this.infowindow.setContent(`
                        <div style='font-size: 13px; line-height: 1.4;'>
                            <strong>${place.name || 'Location'}</strong><br>
                            ${place.formatted_address || ''}<br>
                            <small style='color: #666; font-family: monospace;'>
                                Place ID: ${place.place_id}
                            </small>
                        </div>
                    `);
                    this.infowindow.open(this.map, this.marker);
                },

                applyPlaceId() {
                    if (this.selectedPlace && this.selectedPlace.placeId) {
                        // Set the place_id in the form data
                        @this.set('data.place_id', this.selectedPlace.placeId);

                        new FilamentNotification()
                            .title('Place ID übernommen')
                            .body('Die Place ID wurde erfolgreich eingetragen.')
                            .success()
                            .send();
                    }
                },

                toggleFinder() {
                    this.showFinder = !this.showFinder;
                    if (this.showFinder && !this.map) {
                        this.$nextTick(() => {
                            this.initMap();
                        });
                    }
                }
            }"
            class="space-y-3"
        >
            <!-- Toggle Button -->
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="toggleFinder()"
                    class="inline-flex items-center justify-center gap-2 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 px-4 py-2 text-sm shadow-sm bg-primary-600 text-white border-transparent hover:bg-primary-500 focus:bg-primary-700 focus:ring-primary-500"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!showFinder">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="showFinder" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <span x-text="showFinder ? 'Place ID Finder schließen' : 'Place ID Finder öffnen'"></span>
                </button>
            </div>

            <!-- Map Container -->
            <div
                x-show="showFinder"
                x-transition
                class="border rounded-lg overflow-hidden bg-white dark:bg-gray-800"
                style="display: none;"
            >
                <div class="p-4 bg-gray-50 dark:bg-gray-900 border-b">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Suchen Sie nach einer Location
                    </label>
                    <input
                        x-ref="autocompleteInput"
                        type="text"
                        placeholder="z.B. Eiffelturm, Paris"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
                    />
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Tipp: Sie können auch direkt auf die Karte klicken, um einen Ort auszuwählen
                    </p>
                </div>

                <div x-ref="mapContainer" class="w-full" style="height: 500px;"></div>
            </div>

            <!-- Selected Place Info -->
            <div
                x-show="selectedPlace"
                x-transition
                class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg"
                style="display: none;"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <h4 class="font-semibold text-sm text-gray-900 dark:text-gray-100 mb-1 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Ausgewählte Location:
                        </h4>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="selectedPlace?.name"></p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1" x-text="selectedPlace?.address"></p>
                        <div class="mt-2 p-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Place ID:</p>
                            <code class="text-xs font-mono text-blue-600 dark:text-blue-400" x-text="selectedPlace?.placeId"></code>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="applyPlaceId()"
                        class="inline-flex items-center justify-center gap-2 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 px-4 py-2 text-sm shadow-sm bg-success-600 text-white border-transparent hover:bg-success-500 focus:bg-success-700 whitespace-nowrap"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Place ID übernehmen
                    </button>
                </div>
            </div>
        </div>

        <!-- Load Google Maps Script -->
        <script>
            if (typeof google === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://maps.googleapis.com/maps/api/js?key={{ $apiKey }}&libraries=places&callback=initGoogleMaps';
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);

                window.initGoogleMaps = function() {
                    window.dispatchEvent(new CustomEvent('google-maps-loaded'));
                };
            }
        </script>
    @endif
</x-dynamic-component>
