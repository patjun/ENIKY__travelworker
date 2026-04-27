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
                autocomplete: null,
                selectedPlace: null,
                showFinder: false,

                init() {
                    // Listen for Google Maps loaded event
                    window.addEventListener('google-maps-loaded', () => {
                        if (this.showFinder && !this.autocomplete) {
                            this.$nextTick(() => {
                                this.initAutocomplete();
                            });
                        }
                    });
                },

                initAutocomplete() {
                    const input = this.$refs.autocompleteInput;

                    if (!input) {
                        console.error('Autocomplete input not found');
                        return;
                    }

                    this.autocomplete = new google.maps.places.Autocomplete(input);
                    this.autocomplete.setFields(['place_id', 'name', 'formatted_address', 'geometry']);

                    this.autocomplete.addListener('place_changed', () => {
                        this.handlePlaceSelect();
                    });
                },

                handlePlaceSelect() {
                    const place = this.autocomplete.getPlace();

                    if (!place.place_id) {
                        return;
                    }

                    this.selectedPlace = {
                        placeId: place.place_id,
                        name: place.name,
                        address: place.formatted_address
                    };

                    // Auto-apply Place ID immediately
                    @this.set('data.place_id', this.selectedPlace.placeId);

                    new FilamentNotification()
                        .title('Place ID übernommen')
                        .body(`${this.selectedPlace.name} wurde ausgewählt.`)
                        .success()
                        .send();
                },

                toggleFinder() {
                    this.showFinder = !this.showFinder;
                    if (this.showFinder && !this.autocomplete) {
                        this.$nextTick(() => {
                            if (typeof google !== 'undefined' && google.maps) {
                                this.initAutocomplete();
                            } else {
                                console.warn('Google Maps not loaded yet');
                            }
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

            <!-- Autocomplete Search Container -->
            <div
                x-show="showFinder"
                x-transition
                class="border rounded-lg overflow-hidden bg-white dark:bg-gray-800"
                style="display: none;"
            >
                <div class="p-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Suchen Sie nach einer Attraktion
                    </label>
                    <input
                        x-ref="autocompleteInput"
                        type="text"
                        placeholder="z.B. Eiffelturm, Paris"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-gray-800 dark:text-white"
                    />
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Wählen Sie eine Attraktion aus der Liste. Die Place ID wird automatisch übernommen.
                    </p>
                </div>
            </div>

            <!-- Selected Place Info -->
            <div
                x-show="selectedPlace"
                x-transition
                class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg"
                style="display: none;"
            >
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h4 class="font-semibold text-sm text-gray-900 dark:text-gray-100 mb-1">
                            Attraktion ausgewählt
                        </h4>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="selectedPlace?.name"></p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1" x-text="selectedPlace?.address"></p>
                        <div class="mt-2 p-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Place ID:</p>
                            <code class="text-xs font-mono text-blue-600 dark:text-blue-400" x-text="selectedPlace?.placeId"></code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Load Google Maps Script -->
        <script>
            if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
                // Prevent multiple script loads
                if (!window.googleMapsLoading) {
                    window.googleMapsLoading = true;

                    const script = document.createElement('script');
                    script.src = 'https://maps.googleapis.com/maps/api/js?key={{ $apiKey }}&libraries=places&callback=initGoogleMaps';
                    script.async = true;
                    script.defer = true;
                    document.head.appendChild(script);

                    window.initGoogleMaps = function() {
                        window.googleMapsLoading = false;
                        window.googleMapsLoaded = true;
                        window.dispatchEvent(new CustomEvent('google-maps-loaded'));
                        console.log('Google Maps loaded successfully');
                    };
                }
            } else if (window.googleMapsLoaded) {
                // Google Maps already loaded, dispatch event for components that missed it
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('google-maps-loaded'));
                }, 100);
            }
        </script>
    @endif
</x-dynamic-component>
