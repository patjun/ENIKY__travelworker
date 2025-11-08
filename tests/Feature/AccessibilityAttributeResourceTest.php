<?php

namespace Tests\Feature;

use App\Filament\Resources\AccessibilityAttributeResource;
use App\Filament\Resources\AccessibilityAttributeResource\Pages\CreateAccessibilityAttribute;
use App\Filament\Resources\AccessibilityAttributeResource\Pages\EditAccessibilityAttribute;
use App\Filament\Resources\AccessibilityAttributeResource\Pages\ListAccessibilityAttributes;
use App\Models\AccessibilityAttribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccessibilityAttributeResourceTest extends TestCase
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

    public function test_unauthenticated_users_cannot_access_accessibility_attribute_list_page(): void
    {
        $response = $this->get(AccessibilityAttributeResource::getUrl('index'));

        $response->assertRedirect('/admin/login');
    }

    public function test_unauthenticated_users_cannot_access_accessibility_attribute_create_page(): void
    {
        $response = $this->get(AccessibilityAttributeResource::getUrl('create'));

        $response->assertRedirect('/admin/login');
    }

    public function test_unauthenticated_users_cannot_access_accessibility_attribute_edit_page(): void
    {
        $accessibilityAttribute = AccessibilityAttribute::factory()->create();

        $response = $this->get(AccessibilityAttributeResource::getUrl('edit', ['record' => $accessibilityAttribute]));

        $response->assertRedirect('/admin/login');
    }

    // ===== Authenticated User Tests =====

    public function test_authenticated_users_can_render_accessibility_attribute_list_page(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(AccessibilityAttributeResource::getUrl('index'));

        $response->assertSuccessful();
    }

    public function test_authenticated_users_can_render_accessibility_attribute_create_page(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(AccessibilityAttributeResource::getUrl('create'));

        $response->assertSuccessful();
    }

    public function test_authenticated_users_can_render_accessibility_attribute_edit_page(): void
    {
        $this->actingAs(User::factory()->create());
        $accessibilityAttribute = AccessibilityAttribute::factory()->create();

        $response = $this->get(AccessibilityAttributeResource::getUrl('edit', ['record' => $accessibilityAttribute]));

        $response->assertSuccessful();
    }

    // ===== List Page Functionality Tests =====

    public function test_can_list_accessibility_attributes(): void
    {
        $this->actingAs(User::factory()->create());
        
        $accessibilityAttributes = AccessibilityAttribute::factory()->count(10)->create();

        Livewire::test(ListAccessibilityAttributes::class)
            ->assertCanSeeTableRecords($accessibilityAttributes);
    }

    public function test_can_search_accessibility_attributes_by_placeholder(): void
    {
        $this->actingAs(User::factory()->create());

        $targetAttribute = AccessibilityAttribute::factory()->create(['placeholder' => 'wheelchair_access']);
        $otherAttribute = AccessibilityAttribute::factory()->create(['placeholder' => 'hearing_loop']);

        Livewire::test(ListAccessibilityAttributes::class)
            ->searchTable('wheelchair_access')
            ->assertCanSeeTableRecords([$targetAttribute])
            ->assertCanNotSeeTableRecords([$otherAttribute]);
    }

    public function test_can_search_accessibility_attributes_by_english_name(): void
    {
        $this->actingAs(User::factory()->create());

        $targetAttribute = AccessibilityAttribute::factory()->create(['name_en' => 'Wheelchair Accessible']);
        $otherAttribute = AccessibilityAttribute::factory()->create(['name_en' => 'Hearing Loop Available']);

        Livewire::test(ListAccessibilityAttributes::class)
            ->searchTable('Wheelchair')
            ->assertCanSeeTableRecords([$targetAttribute])
            ->assertCanNotSeeTableRecords([$otherAttribute]);
    }

    public function test_can_search_accessibility_attributes_by_german_name(): void
    {
        $this->actingAs(User::factory()->create());

        $targetAttribute = AccessibilityAttribute::factory()->create(['name_de' => 'Rollstuhlzugänglich']);
        $otherAttribute = AccessibilityAttribute::factory()->create(['name_de' => 'Induktionsschleife verfügbar']);

        Livewire::test(ListAccessibilityAttributes::class)
            ->searchTable('Rollstuhl')
            ->assertCanSeeTableRecords([$targetAttribute])
            ->assertCanNotSeeTableRecords([$otherAttribute]);
    }

    public function test_can_sort_accessibility_attributes_by_placeholder(): void
    {
        $this->actingAs(User::factory()->create());

        $attributeA = AccessibilityAttribute::factory()->create(['placeholder' => 'aaa_access']);
        $attributeB = AccessibilityAttribute::factory()->create(['placeholder' => 'zzz_access']);

        Livewire::test(ListAccessibilityAttributes::class)
            ->sortTable('placeholder')
            ->assertCanSeeTableRecords([$attributeA, $attributeB], inOrder: true);
    }

    // ===== Create Page Functionality Tests =====

    public function test_can_create_accessibility_attribute(): void
    {
        $this->actingAs(User::factory()->create());

        $newData = AccessibilityAttribute::factory()->make();

        Livewire::test(CreateAccessibilityAttribute::class)
            ->fillForm([
                'placeholder' => $newData->placeholder,
                'name_en' => $newData->name_en,
                'name_de' => $newData->name_de,
                'description_en' => $newData->description_en,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(AccessibilityAttribute::class, [
            'placeholder' => $newData->placeholder,
            'name_en' => $newData->name_en,
            'name_de' => $newData->name_de,
            'description_en' => $newData->description_en,
        ]);
    }

    public function test_accessibility_attribute_create_form_validation_placeholder_required(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAccessibilityAttribute::class)
            ->fillForm([
                'placeholder' => null,
                'name_en' => 'Test Name',
                'name_de' => 'Test Name DE',
                'description_en' => 'Test description',
            ])
            ->call('create')
            ->assertHasFormErrors(['placeholder' => 'required']);
    }

    public function test_accessibility_attribute_create_form_validation_name_en_required(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAccessibilityAttribute::class)
            ->fillForm([
                'placeholder' => 'test_placeholder',
                'name_en' => null,
                'name_de' => 'Test Name DE',
                'description_en' => 'Test description',
            ])
            ->call('create')
            ->assertHasFormErrors(['name_en' => 'required']);
    }

    public function test_accessibility_attribute_create_form_validation_name_de_required(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAccessibilityAttribute::class)
            ->fillForm([
                'placeholder' => 'test_placeholder',
                'name_en' => 'Test Name',
                'name_de' => null,
                'description_en' => 'Test description',
            ])
            ->call('create')
            ->assertHasFormErrors(['name_de' => 'required']);
    }

    public function test_accessibility_attribute_create_form_validation_description_en_required(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAccessibilityAttribute::class)
            ->fillForm([
                'placeholder' => 'test_placeholder',
                'name_en' => 'Test Name',
                'name_de' => 'Test Name DE',
                'description_en' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['description_en' => 'required']);
    }

    public function test_accessibility_attribute_create_form_validation_placeholder_unique(): void
    {
        $this->actingAs(User::factory()->create());

        $existingAttribute = AccessibilityAttribute::factory()->create(['placeholder' => 'existing_placeholder']);

        Livewire::test(CreateAccessibilityAttribute::class)
            ->fillForm([
                'placeholder' => 'existing_placeholder',
                'name_en' => 'Test Name',
                'name_de' => 'Test Name DE',
                'description_en' => 'Test description',
            ])
            ->call('create')
            ->assertHasFormErrors(['placeholder']);
    }

    public function test_accessibility_attribute_create_form_validation_placeholder_max_length(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAccessibilityAttribute::class)
            ->fillForm([
                'placeholder' => str_repeat('a', 256), // 256 characters, should exceed max length of 255
                'name_en' => 'Test Name',
                'name_de' => 'Test Name DE',
                'description_en' => 'Test description',
            ])
            ->call('create')
            ->assertHasFormErrors(['placeholder']);
    }

    public function test_accessibility_attribute_create_form_validation_name_en_max_length(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAccessibilityAttribute::class)
            ->fillForm([
                'placeholder' => 'test_placeholder',
                'name_en' => str_repeat('a', 256), // 256 characters, should exceed max length of 255
                'name_de' => 'Test Name DE',
                'description_en' => 'Test description',
            ])
            ->call('create')
            ->assertHasFormErrors(['name_en']);
    }

    public function test_accessibility_attribute_create_form_validation_name_de_max_length(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAccessibilityAttribute::class)
            ->fillForm([
                'placeholder' => 'test_placeholder',
                'name_en' => 'Test Name',
                'name_de' => str_repeat('a', 256), // 256 characters, should exceed max length of 255
                'description_en' => 'Test description',
            ])
            ->call('create')
            ->assertHasFormErrors(['name_de']);
    }

    // ===== Edit Page Functionality Tests =====

    public function test_can_retrieve_accessibility_attribute_data_in_edit_form(): void
    {
        $this->actingAs(User::factory()->create());
        
        $accessibilityAttribute = AccessibilityAttribute::factory()->create();

        Livewire::test(EditAccessibilityAttribute::class, [
            'record' => $accessibilityAttribute->getRouteKey(),
        ])
            ->assertFormSet([
                'placeholder' => $accessibilityAttribute->placeholder,
                'name_en' => $accessibilityAttribute->name_en,
                'name_de' => $accessibilityAttribute->name_de,
                'description_en' => $accessibilityAttribute->description_en,
            ]);
    }

    public function test_can_save_accessibility_attribute(): void
    {
        $this->actingAs(User::factory()->create());
        
        $accessibilityAttribute = AccessibilityAttribute::factory()->create();
        $newData = AccessibilityAttribute::factory()->make();

        Livewire::test(EditAccessibilityAttribute::class, [
            'record' => $accessibilityAttribute->getRouteKey(),
        ])
            ->fillForm([
                'placeholder' => $newData->placeholder,
                'name_en' => $newData->name_en,
                'name_de' => $newData->name_de,
                'description_en' => $newData->description_en,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $accessibilityAttribute->refresh();
        
        $this->assertEquals($newData->placeholder, $accessibilityAttribute->placeholder);
        $this->assertEquals($newData->name_en, $accessibilityAttribute->name_en);
        $this->assertEquals($newData->name_de, $accessibilityAttribute->name_de);
        $this->assertEquals($newData->description_en, $accessibilityAttribute->description_en);
    }

    public function test_accessibility_attribute_edit_form_validation(): void
    {
        $this->actingAs(User::factory()->create());
        
        $accessibilityAttribute = AccessibilityAttribute::factory()->create();

        Livewire::test(EditAccessibilityAttribute::class, [
            'record' => $accessibilityAttribute->getRouteKey(),
        ])
            ->fillForm([
                'placeholder' => null,
                'name_en' => null,
                'name_de' => null,
                'description_en' => null,
            ])
            ->call('save')
            ->assertHasFormErrors([
                'placeholder' => 'required',
                'name_en' => 'required',
                'name_de' => 'required',
                'description_en' => 'required',
            ]);
    }

    public function test_accessibility_attribute_edit_form_placeholder_unique_validation_ignores_current_record(): void
    {
        $this->actingAs(User::factory()->create());
        
        $accessibilityAttribute = AccessibilityAttribute::factory()->create(['placeholder' => 'original_placeholder']);
        $otherAttribute = AccessibilityAttribute::factory()->create(['placeholder' => 'other_placeholder']);

        // Should allow keeping the same placeholder
        Livewire::test(EditAccessibilityAttribute::class, [
            'record' => $accessibilityAttribute->getRouteKey(),
        ])
            ->fillForm([
                'placeholder' => 'original_placeholder',
                'name_en' => 'Updated Name',
                'name_de' => 'Updated Name DE',
                'description_en' => 'Updated description',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Should not allow using another record's placeholder
        Livewire::test(EditAccessibilityAttribute::class, [
            'record' => $accessibilityAttribute->getRouteKey(),
        ])
            ->fillForm([
                'placeholder' => 'other_placeholder',
                'name_en' => 'Updated Name',
                'name_de' => 'Updated Name DE',
                'description_en' => 'Updated description',
            ])
            ->call('save')
            ->assertHasFormErrors(['placeholder']);
    }

    public function test_can_delete_accessibility_attribute(): void
    {
        $this->actingAs(User::factory()->create());
        
        $accessibilityAttribute = AccessibilityAttribute::factory()->create();

        Livewire::test(EditAccessibilityAttribute::class, [
            'record' => $accessibilityAttribute->getRouteKey(),
        ])
            ->callAction('delete');

        $this->assertModelMissing($accessibilityAttribute);
    }

    // ===== Table Actions Tests =====

    public function test_can_edit_accessibility_attribute_from_table(): void
    {
        $this->actingAs(User::factory()->create());
        
        $accessibilityAttribute = AccessibilityAttribute::factory()->create();

        Livewire::test(ListAccessibilityAttributes::class)
            ->callTableAction('edit', $accessibilityAttribute);

        // The record should still exist and be editable
        $this->assertModelExists($accessibilityAttribute);
    }

    public function test_can_delete_accessibility_attribute_from_table(): void
    {
        $this->actingAs(User::factory()->create());
        
        $accessibilityAttribute = AccessibilityAttribute::factory()->create();

        Livewire::test(ListAccessibilityAttributes::class)
            ->callTableAction('delete', $accessibilityAttribute);

        $this->assertModelMissing($accessibilityAttribute);
    }

    public function test_can_bulk_delete_accessibility_attributes(): void
    {
        $this->actingAs(User::factory()->create());
        
        $accessibilityAttributes = AccessibilityAttribute::factory()->count(3)->create();

        Livewire::test(ListAccessibilityAttributes::class)
            ->callTableBulkAction('delete', $accessibilityAttributes);

        foreach ($accessibilityAttributes as $attribute) {
            $this->assertModelMissing($attribute);
        }
    }

    // ===== Form Component Visibility Tests =====

    public function test_create_form_displays_all_required_fields(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAccessibilityAttribute::class)
            ->assertFormExists()
            ->assertFormFieldExists('placeholder')
            ->assertFormFieldExists('name_en')
            ->assertFormFieldExists('name_de')
            ->assertFormFieldExists('description_en');
    }

    public function test_edit_form_displays_all_required_fields(): void
    {
        $this->actingAs(User::factory()->create());
        
        $accessibilityAttribute = AccessibilityAttribute::factory()->create();

        Livewire::test(EditAccessibilityAttribute::class, [
            'record' => $accessibilityAttribute->getRouteKey(),
        ])
            ->assertFormExists()
            ->assertFormFieldExists('placeholder')
            ->assertFormFieldExists('name_en')
            ->assertFormFieldExists('name_de')
            ->assertFormFieldExists('description_en');
    }

    // ===== Table Column Visibility Tests =====

    public function test_table_displays_correct_columns(): void
    {
        $this->actingAs(User::factory()->create());
        
        $accessibilityAttribute = AccessibilityAttribute::factory()->create();

        Livewire::test(ListAccessibilityAttributes::class)
            ->assertTableColumnExists('placeholder')
            ->assertTableColumnExists('name_en')
            ->assertTableColumnExists('name_de')
            ->assertTableColumnExists('description_en')
            ->assertTableColumnExists('created_at');
    }
}
