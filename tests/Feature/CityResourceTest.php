<?php

namespace Tests\Feature;

use App\Filament\Resources\CityResource;
use App\Filament\Resources\CityResource\Pages\CreateCity;
use App\Filament\Resources\CityResource\Pages\EditCity;
use App\Filament\Resources\CityResource\Pages\ListCities;
use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CityResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set the current panel for Filament testing
        \Filament\Facades\Filament::setCurrentPanel(
            \Filament\Facades\Filament::getPanel('admin')
        );
    }

    // ===== Authentication Tests =====

    public function test_unauthenticated_users_cannot_access_city_list_page(): void
    {
        $response = $this->get(CityResource::getUrl('index'));

        $response->assertRedirect('/admin/login');
    }

    public function test_unauthenticated_users_cannot_access_city_create_page(): void
    {
        $response = $this->get(CityResource::getUrl('create'));

        $response->assertRedirect('/admin/login');
    }

    public function test_unauthenticated_users_cannot_access_city_edit_page(): void
    {
        $city = City::factory()->create();

        $response = $this->get(CityResource::getUrl('edit', ['record' => $city]));

        $response->assertRedirect('/admin/login');
    }

    // ===== Authenticated User Tests =====

    public function test_authenticated_users_can_render_city_list_page(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(CityResource::getUrl('index'));

        $response->assertSuccessful();
    }

    public function test_authenticated_users_can_render_city_create_page(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(CityResource::getUrl('create'));

        $response->assertSuccessful();
    }

    public function test_authenticated_users_can_render_city_edit_page(): void
    {
        $this->actingAs(User::factory()->create());
        $city = City::factory()->create();

        $response = $this->get(CityResource::getUrl('edit', ['record' => $city]));

        $response->assertSuccessful();
    }

    // ===== List Page Functionality Tests =====

    public function test_can_list_cities(): void
    {
        $this->actingAs(User::factory()->create());
        
        // Create a specific country to avoid unique constraint issues
        $country = Country::factory()->create(['code' => 'T1']);
        $cities = City::factory()->count(10)->create(['country_id' => $country->id]);

        Livewire::test(ListCities::class)
            ->assertCanSeeTableRecords($cities);
    }

    public function test_can_search_cities_by_name(): void
    {
        $this->actingAs(User::factory()->create());
        $city1 = City::factory()->create(['name_de' => 'München', 'name_en' => 'Munich']);
        $city2 = City::factory()->create(['name_de' => 'Berlin', 'name_en' => 'Berlin']);
        $city3 = City::factory()->create(['name_de' => 'Hamburg', 'name_en' => 'Hamburg']);

        Livewire::test(ListCities::class)
            ->searchTable('München')
            ->assertCanSeeTableRecords([$city1])
            ->assertCanNotSeeTableRecords([$city2, $city3]);
    }

    public function test_can_filter_cities_by_country(): void
    {
        $this->actingAs(User::factory()->create());
        $germany = Country::factory()->create(['name_de' => 'Deutschland', 'code' => 'T2']);
        $austria = Country::factory()->create(['name_de' => 'Österreich', 'code' => 'T3']);
        
        $germanCities = City::factory()->count(3)->create(['country_id' => $germany->id]);
        $austrianCities = City::factory()->count(2)->create(['country_id' => $austria->id]);

        Livewire::test(ListCities::class)
            ->filterTable('country', $germany->id)
            ->assertCanSeeTableRecords($germanCities)
            ->assertCanNotSeeTableRecords($austrianCities);
    }

    // ===== Create Page Functionality Tests =====

    public function test_can_create_city(): void
    {
        $this->actingAs(User::factory()->create());
        $country = Country::factory()->create();

        $newCityData = [
            'country_id' => $country->id,
            'name_de' => 'Frankfurt am Main',
            'name_en' => 'Frankfurt',
        ];

        Livewire::test(CreateCity::class)
            ->fillForm($newCityData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(City::class, $newCityData);
    }

    public function test_create_city_validates_required_fields(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateCity::class)
            ->fillForm([
                'country_id' => null,
                'name_de' => '',
                'name_en' => '',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'country_id' => 'required',
                'name_de' => 'required',
                'name_en' => 'required',
            ]);
    }

    public function test_create_city_validates_country_exists(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateCity::class)
            ->fillForm([
                'country_id' => 99999, // Non-existent country
                'name_de' => 'Test Stadt',
                'name_en' => 'Test City',
            ])
            ->call('create')
            ->assertHasFormErrors(['country_id']);
    }

    public function test_create_city_validates_max_length(): void
    {
        $this->actingAs(User::factory()->create());
        $country = Country::factory()->create();

        Livewire::test(CreateCity::class)
            ->fillForm([
                'country_id' => $country->id,
                'name_de' => str_repeat('a', 256), // Exceeds max length of 255
                'name_en' => str_repeat('b', 256), // Exceeds max length of 255
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name_de' => 'max',
                'name_en' => 'max',
            ]);
    }

    // ===== Edit Page Functionality Tests =====

    public function test_can_retrieve_city_data_for_editing(): void
    {
        $this->actingAs(User::factory()->create());
        $city = City::factory()->create();

        Livewire::test(EditCity::class, [
            'record' => $city->getRouteKey(),
        ])
            ->assertFormSet([
                'country_id' => $city->country_id,
                'name_de' => $city->name_de,
                'name_en' => $city->name_en,
            ]);
    }

    public function test_can_save_city_changes(): void
    {
        $this->actingAs(User::factory()->create());
        $city = City::factory()->create();
        $country = Country::factory()->create();

        $updatedData = [
            'country_id' => $country->id,
            'name_de' => 'Köln',
            'name_en' => 'Cologne',
        ];

        Livewire::test(EditCity::class, [
            'record' => $city->getRouteKey(),
        ])
            ->fillForm($updatedData)
            ->call('save')
            ->assertHasNoFormErrors();

        $city->refresh();
        $this->assertEquals($updatedData['country_id'], $city->country_id);
        $this->assertEquals($updatedData['name_de'], $city->name_de);
        $this->assertEquals($updatedData['name_en'], $city->name_en);
    }

    public function test_edit_city_validates_required_fields(): void
    {
        $this->actingAs(User::factory()->create());
        $city = City::factory()->create();

        Livewire::test(EditCity::class, [
            'record' => $city->getRouteKey(),
        ])
            ->fillForm([
                'country_id' => null,
                'name_de' => '',
                'name_en' => '',
            ])
            ->call('save')
            ->assertHasFormErrors([
                'country_id' => 'required',
                'name_de' => 'required',
                'name_en' => 'required',
            ]);
    }

    public function test_can_delete_city(): void
    {
        $this->actingAs(User::factory()->create());
        $city = City::factory()->create();

        Livewire::test(EditCity::class, [
            'record' => $city->getRouteKey(),
        ])
            ->callAction('delete');

        $this->assertModelMissing($city);
    }

    // ===== Table Action Tests =====

    public function test_can_delete_city_from_table(): void
    {
        $this->actingAs(User::factory()->create());
        $city = City::factory()->create();

        Livewire::test(ListCities::class)
            ->callTableAction('delete', $city);

        $this->assertModelMissing($city);
    }

    public function test_can_bulk_delete_cities(): void
    {
        $this->actingAs(User::factory()->create());
        $cities = City::factory()->count(3)->create();

        Livewire::test(ListCities::class)
            ->callTableBulkAction('delete', $cities);

        foreach ($cities as $city) {
            $this->assertModelMissing($city);
        }
    }

    // ===== Form Component Tests =====

    public function test_country_select_is_searchable_and_preloaded(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateCity::class)
            ->assertFormFieldExists('country_id', function ($field): bool {
                return $field->isSearchable() && $field->isPreloaded();
            });
    }

    public function test_form_has_correct_field_labels(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(CreateCity::class);

        $component->assertFormFieldExists('country_id');
        $component->assertFormFieldExists('name_de');
        $component->assertFormFieldExists('name_en');
    }
}