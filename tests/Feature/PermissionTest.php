<?php

namespace Tests\Feature;

use App\Filament\Pages\AiSettings;
use App\Filament\Resources\AccessibilityAttributeResource;
use App\Filament\Resources\AttractionResource;
use App\Filament\Resources\ChangeResource;
use App\Filament\Resources\CityResource;
use App\Filament\Resources\CountryResource;
use App\Filament\Resources\KeywordResource;
use App\Filament\Resources\ListicleResource;
use App\Filament\Resources\PageResource;
use App\Filament\Resources\PermissionResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use App\Models\AccessibilityAttribute;
use App\Models\Attraction;
use App\Models\City;
use App\Models\Country;
use App\Models\Keyword;
use App\Models\Listicle;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $editor;
    protected User $author;
    protected User $attractionsAuthor;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Rollen und Berechtigungen
        $this->seed(RolePermissionSeeder::class);

        // Erstelle Benutzer mit verschiedenen Rollen
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->editor = User::factory()->create();
        $this->editor->assignRole('editor');

        $this->author = User::factory()->create();
        $this->author->assignRole('author');

        $this->attractionsAuthor = User::factory()->create();
        $this->attractionsAuthor->assignRole('attractions-author');

        // Setze das aktuelle Panel für Filament-Tests
        \Filament\Facades\Filament::setCurrentPanel(
            \Filament\Facades\Filament::getPanel('admin')
        );
    }

    // ===== Content Management Tests =====

    // Listicles Tests
    public function test_super_admin_can_view_listicles(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(ListicleResource::canViewAny());
    }

    public function test_admin_can_view_listicles(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(ListicleResource::canViewAny());
    }

    public function test_editor_can_view_listicles(): void
    {
        $this->actingAs($this->editor);
        $this->assertTrue(ListicleResource::canViewAny());
    }

    public function test_author_can_view_listicles(): void
    {
        $this->actingAs($this->author);
        $this->assertTrue(ListicleResource::canViewAny());
    }

    public function test_attractions_author_cannot_view_listicles(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(ListicleResource::canViewAny());
    }

    public function test_super_admin_can_create_listicles(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(ListicleResource::canCreate());
    }

    public function test_admin_can_create_listicles(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(ListicleResource::canCreate());
    }

    public function test_editor_can_create_listicles(): void
    {
        $this->actingAs($this->editor);
        $this->assertTrue(ListicleResource::canCreate());
    }

    public function test_author_can_create_listicles(): void
    {
        $this->actingAs($this->author);
        $this->assertTrue(ListicleResource::canCreate());
    }

    public function test_attractions_author_cannot_create_listicles(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(ListicleResource::canCreate());
    }

    public function test_super_admin_can_edit_any_listicle(): void
    {
        $this->actingAs($this->superAdmin);
        $listicle = Listicle::factory()->create();
        $this->assertTrue(ListicleResource::canEdit($listicle));
    }

    public function test_admin_can_edit_any_listicle(): void
    {
        $this->actingAs($this->admin);
        $listicle = Listicle::factory()->create();
        $this->assertTrue(ListicleResource::canEdit($listicle));
    }

    public function test_editor_can_edit_any_listicle(): void
    {
        $this->actingAs($this->editor);
        $listicle = Listicle::factory()->create();
        $this->assertTrue(ListicleResource::canEdit($listicle));
    }

    public function test_author_cannot_edit_listicles_without_user_id(): void
    {
        // Note: Currently, Listicles don't have a user_id field in the database.
        // Once user_id is added, authors will only be able to edit their own listicles.
        $this->actingAs($this->author);
        $listicle = Listicle::factory()->create();
        // Since user_id is null, the check will fail
        $this->assertFalse(ListicleResource::canEdit($listicle));
    }

    public function test_attractions_author_cannot_edit_listicles(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $listicle = Listicle::factory()->create();
        $this->assertFalse(ListicleResource::canEdit($listicle));
    }

    public function test_super_admin_can_delete_listicles(): void
    {
        $this->actingAs($this->superAdmin);
        $listicle = Listicle::factory()->create();
        $this->assertTrue(ListicleResource::canDelete($listicle));
    }

    public function test_admin_can_delete_listicles(): void
    {
        $this->actingAs($this->admin);
        $listicle = Listicle::factory()->create();
        $this->assertTrue(ListicleResource::canDelete($listicle));
    }

    public function test_editor_cannot_delete_listicles(): void
    {
        $this->actingAs($this->editor);
        $listicle = Listicle::factory()->create();
        $this->assertFalse(ListicleResource::canDelete($listicle));
    }

    public function test_author_cannot_delete_listicles(): void
    {
        $this->actingAs($this->author);
        $listicle = Listicle::factory()->create();
        $this->assertFalse(ListicleResource::canDelete($listicle));
    }

    public function test_attractions_author_cannot_delete_listicles(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $listicle = Listicle::factory()->create();
        $this->assertFalse(ListicleResource::canDelete($listicle));
    }

    // Pages Tests
    public function test_super_admin_can_view_pages(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(PageResource::canViewAny());
    }

    public function test_admin_can_view_pages(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(PageResource::canViewAny());
    }

    public function test_editor_can_view_pages(): void
    {
        $this->actingAs($this->editor);
        $this->assertTrue(PageResource::canViewAny());
    }

    public function test_author_can_view_pages(): void
    {
        $this->actingAs($this->author);
        $this->assertTrue(PageResource::canViewAny());
    }

    public function test_attractions_author_cannot_view_pages(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(PageResource::canViewAny());
    }

    // ===== Places Management Tests =====

    // Attractions Tests
    public function test_super_admin_can_view_attractions(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(AttractionResource::canViewAny());
    }

    public function test_admin_can_view_attractions(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(AttractionResource::canViewAny());
    }

    public function test_editor_can_view_attractions(): void
    {
        $this->actingAs($this->editor);
        $this->assertTrue(AttractionResource::canViewAny());
    }

    public function test_author_cannot_view_attractions(): void
    {
        $this->actingAs($this->author);
        $this->assertFalse(AttractionResource::canViewAny());
    }

    public function test_attractions_author_can_view_attractions(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertTrue(AttractionResource::canViewAny());
    }

    public function test_super_admin_can_create_attractions(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(AttractionResource::canCreate());
    }

    public function test_admin_can_create_attractions(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(AttractionResource::canCreate());
    }

    public function test_editor_can_create_attractions(): void
    {
        $this->actingAs($this->editor);
        $this->assertTrue(AttractionResource::canCreate());
    }

    public function test_author_cannot_create_attractions(): void
    {
        $this->actingAs($this->author);
        $this->assertFalse(AttractionResource::canCreate());
    }

    public function test_attractions_author_can_create_attractions(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertTrue(AttractionResource::canCreate());
    }

    public function test_super_admin_can_edit_attractions(): void
    {
        $this->actingAs($this->superAdmin);
        $attraction = Attraction::factory()->create();
        $this->assertTrue(AttractionResource::canEdit($attraction));
    }

    public function test_admin_can_edit_attractions(): void
    {
        $this->actingAs($this->admin);
        $attraction = Attraction::factory()->create();
        $this->assertTrue(AttractionResource::canEdit($attraction));
    }

    public function test_editor_can_edit_attractions(): void
    {
        $this->actingAs($this->editor);
        $attraction = Attraction::factory()->create();
        $this->assertTrue(AttractionResource::canEdit($attraction));
    }

    public function test_author_cannot_edit_attractions(): void
    {
        $this->actingAs($this->author);
        $attraction = Attraction::factory()->create();
        $this->assertFalse(AttractionResource::canEdit($attraction));
    }

    public function test_attractions_author_can_edit_attractions(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $attraction = Attraction::factory()->create();
        $this->assertTrue(AttractionResource::canEdit($attraction));
    }

    public function test_super_admin_can_delete_attractions(): void
    {
        $this->actingAs($this->superAdmin);
        $attraction = Attraction::factory()->create();
        $this->assertTrue(AttractionResource::canDelete($attraction));
    }

    public function test_admin_can_delete_attractions(): void
    {
        $this->actingAs($this->admin);
        $attraction = Attraction::factory()->create();
        $this->assertTrue(AttractionResource::canDelete($attraction));
    }

    public function test_editor_cannot_delete_attractions(): void
    {
        $this->actingAs($this->editor);
        $attraction = Attraction::factory()->create();
        $this->assertFalse(AttractionResource::canDelete($attraction));
    }

    public function test_author_cannot_delete_attractions(): void
    {
        $this->actingAs($this->author);
        $attraction = Attraction::factory()->create();
        $this->assertFalse(AttractionResource::canDelete($attraction));
    }

    public function test_attractions_author_cannot_delete_attractions(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $attraction = Attraction::factory()->create();
        $this->assertFalse(AttractionResource::canDelete($attraction));
    }

    // Cities Tests
    public function test_super_admin_can_view_cities(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(CityResource::canViewAny());
    }

    public function test_admin_can_view_cities(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(CityResource::canViewAny());
    }

    public function test_editor_can_view_cities(): void
    {
        $this->actingAs($this->editor);
        $this->assertTrue(CityResource::canViewAny());
    }

    public function test_author_can_view_cities(): void
    {
        $this->actingAs($this->author);
        $this->assertTrue(CityResource::canViewAny());
    }

    public function test_attractions_author_can_view_cities(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertTrue(CityResource::canViewAny());
    }

    public function test_super_admin_can_create_cities(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(CityResource::canCreate());
    }

    public function test_admin_can_create_cities(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(CityResource::canCreate());
    }

    public function test_editor_can_create_cities(): void
    {
        $this->actingAs($this->editor);
        $this->assertTrue(CityResource::canCreate());
    }

    public function test_author_cannot_create_cities(): void
    {
        $this->actingAs($this->author);
        $this->assertFalse(CityResource::canCreate());
    }

    public function test_attractions_author_cannot_create_cities(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(CityResource::canCreate());
    }

    public function test_super_admin_can_edit_cities(): void
    {
        $this->actingAs($this->superAdmin);
        $city = City::factory()->create();
        $this->assertTrue(CityResource::canEdit($city));
    }

    public function test_admin_can_edit_cities(): void
    {
        $this->actingAs($this->admin);
        $city = City::factory()->create();
        $this->assertTrue(CityResource::canEdit($city));
    }

    public function test_editor_can_edit_cities(): void
    {
        $this->actingAs($this->editor);
        $city = City::factory()->create();
        $this->assertTrue(CityResource::canEdit($city));
    }

    public function test_author_cannot_edit_cities(): void
    {
        $this->actingAs($this->author);
        $city = City::factory()->create();
        $this->assertFalse(CityResource::canEdit($city));
    }

    public function test_attractions_author_cannot_edit_cities(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $city = City::factory()->create();
        $this->assertFalse(CityResource::canEdit($city));
    }

    public function test_super_admin_can_delete_cities(): void
    {
        $this->actingAs($this->superAdmin);
        $city = City::factory()->create();
        $this->assertTrue(CityResource::canDelete($city));
    }

    public function test_admin_can_delete_cities(): void
    {
        $this->actingAs($this->admin);
        $city = City::factory()->create();
        $this->assertTrue(CityResource::canDelete($city));
    }

    public function test_editor_cannot_delete_cities(): void
    {
        $this->actingAs($this->editor);
        $city = City::factory()->create();
        $this->assertFalse(CityResource::canDelete($city));
    }

    public function test_author_cannot_delete_cities(): void
    {
        $this->actingAs($this->author);
        $city = City::factory()->create();
        $this->assertFalse(CityResource::canDelete($city));
    }

    public function test_attractions_author_cannot_delete_cities(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $city = City::factory()->create();
        $this->assertFalse(CityResource::canDelete($city));
    }

    // Countries Tests
    public function test_super_admin_can_view_countries(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(CountryResource::canViewAny());
    }

    public function test_admin_can_view_countries(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(CountryResource::canViewAny());
    }

    public function test_editor_can_view_countries(): void
    {
        $this->actingAs($this->editor);
        $this->assertTrue(CountryResource::canViewAny());
    }

    public function test_author_can_view_countries(): void
    {
        $this->actingAs($this->author);
        $this->assertTrue(CountryResource::canViewAny());
    }

    public function test_attractions_author_can_view_countries(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertTrue(CountryResource::canViewAny());
    }

    public function test_super_admin_can_create_countries(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(CountryResource::canCreate());
    }

    public function test_admin_can_create_countries(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(CountryResource::canCreate());
    }

    public function test_editor_can_create_countries(): void
    {
        $this->actingAs($this->editor);
        $this->assertTrue(CountryResource::canCreate());
    }

    public function test_author_cannot_create_countries(): void
    {
        $this->actingAs($this->author);
        $this->assertFalse(CountryResource::canCreate());
    }

    public function test_attractions_author_cannot_create_countries(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(CountryResource::canCreate());
    }

    public function test_super_admin_can_edit_countries(): void
    {
        $this->actingAs($this->superAdmin);
        $country = Country::factory()->create();
        $this->assertTrue(CountryResource::canEdit($country));
    }

    public function test_admin_can_edit_countries(): void
    {
        $this->actingAs($this->admin);
        $country = Country::factory()->create();
        $this->assertTrue(CountryResource::canEdit($country));
    }

    public function test_editor_can_edit_countries(): void
    {
        $this->actingAs($this->editor);
        $country = Country::factory()->create();
        $this->assertTrue(CountryResource::canEdit($country));
    }

    public function test_author_cannot_edit_countries(): void
    {
        $this->actingAs($this->author);
        $country = Country::factory()->create();
        $this->assertFalse(CountryResource::canEdit($country));
    }

    public function test_attractions_author_cannot_edit_countries(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $country = Country::factory()->create();
        $this->assertFalse(CountryResource::canEdit($country));
    }

    public function test_super_admin_can_delete_countries(): void
    {
        $this->actingAs($this->superAdmin);
        $country = Country::factory()->create();
        $this->assertTrue(CountryResource::canDelete($country));
    }

    public function test_admin_can_delete_countries(): void
    {
        $this->actingAs($this->admin);
        $country = Country::factory()->create();
        $this->assertTrue(CountryResource::canDelete($country));
    }

    public function test_editor_cannot_delete_countries(): void
    {
        $this->actingAs($this->editor);
        $country = Country::factory()->create();
        $this->assertFalse(CountryResource::canDelete($country));
    }

    public function test_author_cannot_delete_countries(): void
    {
        $this->actingAs($this->author);
        $country = Country::factory()->create();
        $this->assertFalse(CountryResource::canDelete($country));
    }

    public function test_attractions_author_cannot_delete_countries(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $country = Country::factory()->create();
        $this->assertFalse(CountryResource::canDelete($country));
    }

    // Accessibility Attributes Tests
    public function test_super_admin_can_view_accessibility_attributes(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(AccessibilityAttributeResource::canViewAny());
    }

    public function test_admin_can_view_accessibility_attributes(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(AccessibilityAttributeResource::canViewAny());
    }

    public function test_editor_can_view_accessibility_attributes(): void
    {
        $this->actingAs($this->editor);
        $this->assertTrue(AccessibilityAttributeResource::canViewAny());
    }

    public function test_author_can_view_accessibility_attributes(): void
    {
        $this->actingAs($this->author);
        $this->assertTrue(AccessibilityAttributeResource::canViewAny());
    }

    public function test_attractions_author_can_view_accessibility_attributes(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertTrue(AccessibilityAttributeResource::canViewAny());
    }

    public function test_super_admin_can_create_accessibility_attributes(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(AccessibilityAttributeResource::canCreate());
    }

    public function test_admin_can_create_accessibility_attributes(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(AccessibilityAttributeResource::canCreate());
    }

    public function test_editor_can_create_accessibility_attributes(): void
    {
        $this->actingAs($this->editor);
        $this->assertTrue(AccessibilityAttributeResource::canCreate());
    }

    public function test_author_cannot_create_accessibility_attributes(): void
    {
        $this->actingAs($this->author);
        $this->assertFalse(AccessibilityAttributeResource::canCreate());
    }

    public function test_attractions_author_cannot_create_accessibility_attributes(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(AccessibilityAttributeResource::canCreate());
    }

    public function test_super_admin_can_edit_accessibility_attributes(): void
    {
        $this->actingAs($this->superAdmin);
        $attribute = AccessibilityAttribute::factory()->create();
        $this->assertTrue(AccessibilityAttributeResource::canEdit($attribute));
    }

    public function test_admin_can_edit_accessibility_attributes(): void
    {
        $this->actingAs($this->admin);
        $attribute = AccessibilityAttribute::factory()->create();
        $this->assertTrue(AccessibilityAttributeResource::canEdit($attribute));
    }

    public function test_editor_can_edit_accessibility_attributes(): void
    {
        $this->actingAs($this->editor);
        $attribute = AccessibilityAttribute::factory()->create();
        $this->assertTrue(AccessibilityAttributeResource::canEdit($attribute));
    }

    public function test_author_cannot_edit_accessibility_attributes(): void
    {
        $this->actingAs($this->author);
        $attribute = AccessibilityAttribute::factory()->create();
        $this->assertFalse(AccessibilityAttributeResource::canEdit($attribute));
    }

    public function test_attractions_author_cannot_edit_accessibility_attributes(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $attribute = AccessibilityAttribute::factory()->create();
        $this->assertFalse(AccessibilityAttributeResource::canEdit($attribute));
    }

    public function test_super_admin_can_delete_accessibility_attributes(): void
    {
        $this->actingAs($this->superAdmin);
        $attribute = AccessibilityAttribute::factory()->create();
        $this->assertTrue(AccessibilityAttributeResource::canDelete($attribute));
    }

    public function test_admin_can_delete_accessibility_attributes(): void
    {
        $this->actingAs($this->admin);
        $attribute = AccessibilityAttribute::factory()->create();
        $this->assertTrue(AccessibilityAttributeResource::canDelete($attribute));
    }

    public function test_editor_cannot_delete_accessibility_attributes(): void
    {
        $this->actingAs($this->editor);
        $attribute = AccessibilityAttribute::factory()->create();
        $this->assertFalse(AccessibilityAttributeResource::canDelete($attribute));
    }

    public function test_author_cannot_delete_accessibility_attributes(): void
    {
        $this->actingAs($this->author);
        $attribute = AccessibilityAttribute::factory()->create();
        $this->assertFalse(AccessibilityAttributeResource::canDelete($attribute));
    }

    public function test_attractions_author_cannot_delete_accessibility_attributes(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $attribute = AccessibilityAttribute::factory()->create();
        $this->assertFalse(AccessibilityAttributeResource::canDelete($attribute));
    }

    // ===== Keywords & Changes Tests =====

    // Keywords Tests
    public function test_super_admin_can_view_keywords(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(KeywordResource::canViewAny());
    }

    public function test_admin_can_view_keywords(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(KeywordResource::canViewAny());
    }

    public function test_editor_can_view_keywords(): void
    {
        $this->actingAs($this->editor);
        $this->assertTrue(KeywordResource::canViewAny());
    }

    public function test_author_cannot_view_keywords(): void
    {
        $this->actingAs($this->author);
        $this->assertFalse(KeywordResource::canViewAny());
    }

    public function test_attractions_author_cannot_view_keywords(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(KeywordResource::canViewAny());
    }

    public function test_super_admin_can_manage_keywords(): void
    {
        $this->actingAs($this->superAdmin);
        $keyword = Keyword::factory()->create();
        $this->assertTrue(KeywordResource::canCreate());
        $this->assertTrue(KeywordResource::canEdit($keyword));
    }

    public function test_admin_can_manage_keywords(): void
    {
        $this->actingAs($this->admin);
        $keyword = Keyword::factory()->create();
        $this->assertTrue(KeywordResource::canCreate());
        $this->assertTrue(KeywordResource::canEdit($keyword));
    }

    public function test_editor_can_manage_keywords(): void
    {
        $this->actingAs($this->editor);
        $keyword = Keyword::factory()->create();
        $this->assertTrue(KeywordResource::canCreate());
        $this->assertTrue(KeywordResource::canEdit($keyword));
    }

    public function test_author_cannot_manage_keywords(): void
    {
        $this->actingAs($this->author);
        $keyword = Keyword::factory()->create();
        $this->assertFalse(KeywordResource::canCreate());
        $this->assertFalse(KeywordResource::canEdit($keyword));
    }

    public function test_attractions_author_cannot_manage_keywords(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $keyword = Keyword::factory()->create();
        $this->assertFalse(KeywordResource::canCreate());
        $this->assertFalse(KeywordResource::canEdit($keyword));
    }

    public function test_super_admin_can_delete_keywords(): void
    {
        $this->actingAs($this->superAdmin);
        $keyword = Keyword::factory()->create();
        $this->assertTrue(KeywordResource::canDelete($keyword));
    }

    public function test_admin_can_delete_keywords(): void
    {
        $this->actingAs($this->admin);
        $keyword = Keyword::factory()->create();
        $this->assertTrue(KeywordResource::canDelete($keyword));
    }

    public function test_editor_cannot_delete_keywords(): void
    {
        $this->actingAs($this->editor);
        $keyword = Keyword::factory()->create();
        $this->assertFalse(KeywordResource::canDelete($keyword));
    }

    public function test_author_cannot_delete_keywords(): void
    {
        $this->actingAs($this->author);
        $keyword = Keyword::factory()->create();
        $this->assertFalse(KeywordResource::canDelete($keyword));
    }

    public function test_attractions_author_cannot_delete_keywords(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $keyword = Keyword::factory()->create();
        $this->assertFalse(KeywordResource::canDelete($keyword));
    }

    // Changes Tests
    public function test_super_admin_can_view_changes(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(ChangeResource::canViewAny());
    }

    public function test_admin_can_view_changes(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(ChangeResource::canViewAny());
    }

    public function test_editor_can_view_changes(): void
    {
        $this->actingAs($this->editor);
        $this->assertTrue(ChangeResource::canViewAny());
    }

    public function test_author_cannot_view_changes(): void
    {
        $this->actingAs($this->author);
        $this->assertFalse(ChangeResource::canViewAny());
    }

    public function test_attractions_author_cannot_view_changes(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(ChangeResource::canViewAny());
    }

    public function test_super_admin_can_manage_changes(): void
    {
        $this->actingAs($this->superAdmin);
        $change = \App\Models\Change::factory()->create();
        $this->assertTrue(ChangeResource::canCreate());
        $this->assertTrue(ChangeResource::canEdit($change));
    }

    public function test_admin_can_manage_changes(): void
    {
        $this->actingAs($this->admin);
        $change = \App\Models\Change::factory()->create();
        $this->assertTrue(ChangeResource::canCreate());
        $this->assertTrue(ChangeResource::canEdit($change));
    }

    public function test_editor_can_manage_changes(): void
    {
        $this->actingAs($this->editor);
        $change = \App\Models\Change::factory()->create();
        $this->assertTrue(ChangeResource::canCreate());
        $this->assertTrue(ChangeResource::canEdit($change));
    }

    public function test_author_cannot_manage_changes(): void
    {
        $this->actingAs($this->author);
        $change = \App\Models\Change::factory()->create();
        $this->assertFalse(ChangeResource::canCreate());
        $this->assertFalse(ChangeResource::canEdit($change));
    }

    public function test_attractions_author_cannot_manage_changes(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $change = \App\Models\Change::factory()->create();
        $this->assertFalse(ChangeResource::canCreate());
        $this->assertFalse(ChangeResource::canEdit($change));
    }

    public function test_super_admin_can_delete_changes(): void
    {
        $this->actingAs($this->superAdmin);
        $change = \App\Models\Change::factory()->create();
        $this->assertTrue(ChangeResource::canDelete($change));
    }

    public function test_admin_can_delete_changes(): void
    {
        $this->actingAs($this->admin);
        $change = \App\Models\Change::factory()->create();
        $this->assertTrue(ChangeResource::canDelete($change));
    }

    public function test_editor_cannot_delete_changes(): void
    {
        $this->actingAs($this->editor);
        $change = \App\Models\Change::factory()->create();
        $this->assertFalse(ChangeResource::canDelete($change));
    }

    public function test_author_cannot_delete_changes(): void
    {
        $this->actingAs($this->author);
        $change = \App\Models\Change::factory()->create();
        $this->assertFalse(ChangeResource::canDelete($change));
    }

    public function test_attractions_author_cannot_delete_changes(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $change = \App\Models\Change::factory()->create();
        $this->assertFalse(ChangeResource::canDelete($change));
    }

    // ===== Settings Tests =====

    // Users Tests
    public function test_super_admin_can_view_users(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(UserResource::canViewAny());
    }

    public function test_admin_can_view_users(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(UserResource::canViewAny());
    }

    public function test_editor_cannot_view_users(): void
    {
        $this->actingAs($this->editor);
        $this->assertFalse(UserResource::canViewAny());
    }

    public function test_author_cannot_view_users(): void
    {
        $this->actingAs($this->author);
        $this->assertFalse(UserResource::canViewAny());
    }

    public function test_attractions_author_cannot_view_users(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(UserResource::canViewAny());
    }

    public function test_super_admin_can_create_users(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(UserResource::canCreate());
    }

    public function test_admin_can_create_users(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(UserResource::canCreate());
    }

    public function test_editor_cannot_create_users(): void
    {
        $this->actingAs($this->editor);
        $this->assertFalse(UserResource::canCreate());
    }

    public function test_author_cannot_create_users(): void
    {
        $this->actingAs($this->author);
        $this->assertFalse(UserResource::canCreate());
    }

    public function test_attractions_author_cannot_create_users(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(UserResource::canCreate());
    }

    public function test_super_admin_can_edit_users(): void
    {
        $this->actingAs($this->superAdmin);
        $user = User::factory()->create();
        $this->assertTrue(UserResource::canEdit($user));
    }

    public function test_admin_can_edit_users(): void
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create();
        $this->assertTrue(UserResource::canEdit($user));
    }

    public function test_editor_cannot_edit_users(): void
    {
        $this->actingAs($this->editor);
        $user = User::factory()->create();
        $this->assertFalse(UserResource::canEdit($user));
    }

    public function test_author_cannot_edit_users(): void
    {
        $this->actingAs($this->author);
        $user = User::factory()->create();
        $this->assertFalse(UserResource::canEdit($user));
    }

    public function test_attractions_author_cannot_edit_users(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $user = User::factory()->create();
        $this->assertFalse(UserResource::canEdit($user));
    }

    public function test_super_admin_can_delete_users(): void
    {
        $this->actingAs($this->superAdmin);
        $user = User::factory()->create();
        $this->assertTrue(UserResource::canDelete($user));
    }

    public function test_admin_can_delete_users(): void
    {
        $this->actingAs($this->admin);
        $user = User::factory()->create();
        $this->assertTrue(UserResource::canDelete($user));
    }

    public function test_editor_cannot_delete_users(): void
    {
        $this->actingAs($this->editor);
        $user = User::factory()->create();
        $this->assertFalse(UserResource::canDelete($user));
    }

    public function test_author_cannot_delete_users(): void
    {
        $this->actingAs($this->author);
        $user = User::factory()->create();
        $this->assertFalse(UserResource::canDelete($user));
    }

    public function test_attractions_author_cannot_delete_users(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $user = User::factory()->create();
        $this->assertFalse(UserResource::canDelete($user));
    }

    // Super Admin - Complete User Permissions Tests
    public function test_super_admin_has_view_users_permission(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue($this->superAdmin->can('view users'));
    }

    public function test_super_admin_has_create_users_permission(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue($this->superAdmin->can('create users'));
    }

    public function test_super_admin_has_edit_users_permission(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue($this->superAdmin->can('edit users'));
    }

    public function test_super_admin_has_delete_users_permission(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue($this->superAdmin->can('delete users'));
    }

    public function test_super_admin_has_manage_users_permission(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue($this->superAdmin->can('manage users'));
    }

    public function test_super_admin_can_access_user_resource_navigation(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(UserResource::shouldRegisterNavigation());
    }

    // Roles Tests
    public function test_super_admin_can_view_roles(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(RoleResource::canViewAny());
    }

    public function test_admin_cannot_view_roles(): void
    {
        $this->actingAs($this->admin);
        $this->assertFalse(RoleResource::canViewAny());
    }

    public function test_editor_cannot_view_roles(): void
    {
        $this->actingAs($this->editor);
        $this->assertFalse(RoleResource::canViewAny());
    }

    public function test_author_cannot_view_roles(): void
    {
        $this->actingAs($this->author);
        $this->assertFalse(RoleResource::canViewAny());
    }

    public function test_attractions_author_cannot_view_roles(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(RoleResource::canViewAny());
    }

    public function test_super_admin_can_create_roles(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(RoleResource::canCreate());
    }

    public function test_admin_cannot_create_roles(): void
    {
        $this->actingAs($this->admin);
        $this->assertFalse(RoleResource::canCreate());
    }

    public function test_editor_cannot_create_roles(): void
    {
        $this->actingAs($this->editor);
        $this->assertFalse(RoleResource::canCreate());
    }

    public function test_author_cannot_create_roles(): void
    {
        $this->actingAs($this->author);
        $this->assertFalse(RoleResource::canCreate());
    }

    public function test_attractions_author_cannot_create_roles(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(RoleResource::canCreate());
    }

    public function test_super_admin_can_edit_roles(): void
    {
        $this->actingAs($this->superAdmin);
        $role = Role::findByName('admin');
        $this->assertTrue(RoleResource::canEdit($role));
    }

    public function test_admin_cannot_edit_roles(): void
    {
        $this->actingAs($this->admin);
        $role = Role::findByName('editor');
        $this->assertFalse(RoleResource::canEdit($role));
    }

    public function test_editor_cannot_edit_roles(): void
    {
        $this->actingAs($this->editor);
        $role = Role::findByName('author');
        $this->assertFalse(RoleResource::canEdit($role));
    }

    public function test_author_cannot_edit_roles(): void
    {
        $this->actingAs($this->author);
        $role = Role::findByName('attractions-author');
        $this->assertFalse(RoleResource::canEdit($role));
    }

    public function test_attractions_author_cannot_edit_roles(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $role = Role::findByName('editor');
        $this->assertFalse(RoleResource::canEdit($role));
    }

    public function test_super_admin_can_delete_roles(): void
    {
        $this->actingAs($this->superAdmin);
        $role = Role::create(['name' => 'test-role']);
        $this->assertTrue(RoleResource::canDelete($role));
        $role->delete();
    }

    public function test_admin_cannot_delete_roles(): void
    {
        $this->actingAs($this->admin);
        $role = Role::create(['name' => 'test-role-2']);
        $this->assertFalse(RoleResource::canDelete($role));
        $role->delete();
    }

    public function test_editor_cannot_delete_roles(): void
    {
        $this->actingAs($this->editor);
        $role = Role::create(['name' => 'test-role-3']);
        $this->assertFalse(RoleResource::canDelete($role));
        $role->delete();
    }

    public function test_author_cannot_delete_roles(): void
    {
        $this->actingAs($this->author);
        $role = Role::create(['name' => 'test-role-4']);
        $this->assertFalse(RoleResource::canDelete($role));
        $role->delete();
    }

    public function test_attractions_author_cannot_delete_roles(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $role = Role::create(['name' => 'test-role-5']);
        $this->assertFalse(RoleResource::canDelete($role));
        $role->delete();
    }

    // Super Admin - Complete Role Permissions Tests
    public function test_super_admin_has_view_roles_permission(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue($this->superAdmin->can('view roles'));
    }

    public function test_super_admin_has_create_roles_permission(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue($this->superAdmin->can('create roles'));
    }

    public function test_super_admin_has_edit_roles_permission(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue($this->superAdmin->can('edit roles'));
    }

    public function test_super_admin_has_delete_roles_permission(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue($this->superAdmin->can('delete roles'));
    }

    public function test_super_admin_has_manage_roles_permission(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue($this->superAdmin->can('manage roles'));
    }

    public function test_super_admin_can_access_role_resource_navigation(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(RoleResource::shouldRegisterNavigation());
    }

    // Permissions Tests
    public function test_super_admin_can_view_permissions(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(PermissionResource::canViewAny());
    }

    public function test_admin_cannot_view_permissions(): void
    {
        $this->actingAs($this->admin);
        $this->assertFalse(PermissionResource::canViewAny());
    }

    public function test_editor_cannot_view_permissions(): void
    {
        $this->actingAs($this->editor);
        $this->assertFalse(PermissionResource::canViewAny());
    }

    public function test_author_cannot_view_permissions(): void
    {
        $this->actingAs($this->author);
        $this->assertFalse(PermissionResource::canViewAny());
    }

    public function test_attractions_author_cannot_view_permissions(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(PermissionResource::canViewAny());
    }

    public function test_super_admin_can_create_permissions(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(PermissionResource::canCreate());
    }

    public function test_admin_cannot_create_permissions(): void
    {
        $this->actingAs($this->admin);
        $this->assertFalse(PermissionResource::canCreate());
    }

    public function test_editor_cannot_create_permissions(): void
    {
        $this->actingAs($this->editor);
        $this->assertFalse(PermissionResource::canCreate());
    }

    public function test_author_cannot_create_permissions(): void
    {
        $this->actingAs($this->author);
        $this->assertFalse(PermissionResource::canCreate());
    }

    public function test_attractions_author_cannot_create_permissions(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(PermissionResource::canCreate());
    }

    public function test_super_admin_can_edit_permissions(): void
    {
        $this->actingAs($this->superAdmin);
        $permission = Permission::findByName('view posts');
        $this->assertTrue(PermissionResource::canEdit($permission));
    }

    public function test_admin_cannot_edit_permissions(): void
    {
        $this->actingAs($this->admin);
        $permission = Permission::findByName('view posts');
        $this->assertFalse(PermissionResource::canEdit($permission));
    }

    public function test_editor_cannot_edit_permissions(): void
    {
        $this->actingAs($this->editor);
        $permission = Permission::findByName('view posts');
        $this->assertFalse(PermissionResource::canEdit($permission));
    }

    public function test_author_cannot_edit_permissions(): void
    {
        $this->actingAs($this->author);
        $permission = Permission::findByName('view posts');
        $this->assertFalse(PermissionResource::canEdit($permission));
    }

    public function test_attractions_author_cannot_edit_permissions(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $permission = Permission::findByName('view posts');
        $this->assertFalse(PermissionResource::canEdit($permission));
    }

    public function test_super_admin_can_delete_permissions(): void
    {
        $this->actingAs($this->superAdmin);
        $permission = Permission::create(['name' => 'test-permission']);
        $this->assertTrue(PermissionResource::canDelete($permission));
        $permission->delete();
    }

    public function test_admin_cannot_delete_permissions(): void
    {
        $this->actingAs($this->admin);
        $permission = Permission::create(['name' => 'test-permission-2']);
        $this->assertFalse(PermissionResource::canDelete($permission));
        $permission->delete();
    }

    public function test_editor_cannot_delete_permissions(): void
    {
        $this->actingAs($this->editor);
        $permission = Permission::create(['name' => 'test-permission-3']);
        $this->assertFalse(PermissionResource::canDelete($permission));
        $permission->delete();
    }

    public function test_author_cannot_delete_permissions(): void
    {
        $this->actingAs($this->author);
        $permission = Permission::create(['name' => 'test-permission-4']);
        $this->assertFalse(PermissionResource::canDelete($permission));
        $permission->delete();
    }

    public function test_attractions_author_cannot_delete_permissions(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $permission = Permission::create(['name' => 'test-permission-5']);
        $this->assertFalse(PermissionResource::canDelete($permission));
        $permission->delete();
    }

    // AI Settings Tests
    public function test_super_admin_can_access_ai_settings(): void
    {
        $this->actingAs($this->superAdmin);
        $this->assertTrue(AiSettings::canAccess());
    }

    public function test_admin_can_access_ai_settings(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(AiSettings::canAccess());
    }

    public function test_editor_can_access_ai_settings(): void
    {
        $this->actingAs($this->editor);
        $this->assertTrue(AiSettings::canAccess());
    }

    public function test_author_cannot_access_ai_settings(): void
    {
        $this->actingAs($this->author);
        $this->assertFalse(AiSettings::canAccess());
    }

    public function test_attractions_author_cannot_access_ai_settings(): void
    {
        $this->actingAs($this->attractionsAuthor);
        $this->assertFalse(AiSettings::canAccess());
    }
}

