<?php

namespace Tests\Feature;

use App\Filament\Resources\ListicleResource;
use App\Filament\Resources\ListicleResource\Pages\CreateListicle;
use App\Filament\Resources\ListicleResource\Pages\EditListicle;
use App\Models\Listicle;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ListicleResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Authenticate as a user for Filament access
        $this->actingAs($this->createUserWithRole('admin'));

        // Set the current panel for Filament testing
        \Filament\Facades\Filament::setCurrentPanel(
            \Filament\Facades\Filament::getPanel('admin')
        );
    }

    public function test_intro_de_rich_editor_has_disabled_toolbar_buttons(): void
    {
        Livewire::test(CreateListicle::class)
            ->assertFormFieldExists('intro_de', function (RichEditor $field): bool {
                $toolbarButtons = $field->getToolbarButtons();

                return ! in_array('attachFiles', $toolbarButtons)
                    && ! in_array('codeBlock', $toolbarButtons);
            });
    }

    public function test_intro_en_rich_editor_has_disabled_toolbar_buttons(): void
    {
        Livewire::test(CreateListicle::class)
            ->assertFormFieldExists('intro_en', function (RichEditor $field): bool {
                $toolbarButtons = $field->getToolbarButtons();

                return ! in_array('attachFiles', $toolbarButtons)
                    && ! in_array('codeBlock', $toolbarButtons);
            });
    }

    public function test_custom_intro_rich_editor_has_disabled_toolbar_buttons_in_edit_form(): void
    {
        $listicle = Listicle::factory()->create();

        Livewire::test(EditListicle::class, ['record' => $listicle->getRouteKey()])
            ->assertFormFieldExists('intro_de', function (RichEditor $field): bool {
                $toolbarButtons = $field->getToolbarButtons();

                return ! in_array('attachFiles', $toolbarButtons)
                    && ! in_array('codeBlock', $toolbarButtons);
            })
            ->assertFormFieldExists('intro_en', function (RichEditor $field): bool {
                $toolbarButtons = $field->getToolbarButtons();

                return ! in_array('attachFiles', $toolbarButtons)
                    && ! in_array('codeBlock', $toolbarButtons);
            });
    }

    public function test_image_de_field_exists_with_correct_configuration(): void
    {
        Livewire::test(CreateListicle::class)
            ->assertFormFieldExists('image_de', function (FileUpload $field): bool {
                return $field->getAcceptedFileTypes() === ['image/jpeg', 'image/png']
                    && $field->getDiskName() === 'public'
                    && $field->getDirectory() === 'listicle-images';
            });
    }

    public function test_image_en_field_exists_with_correct_configuration(): void
    {
        Livewire::test(CreateListicle::class)
            ->assertFormFieldExists('image_en', function (FileUpload $field): bool {
                return $field->getAcceptedFileTypes() === ['image/jpeg', 'image/png']
                    && $field->getDiskName() === 'public'
                    && $field->getDirectory() === 'listicle-images';
            });
    }

    public function test_can_upload_image_de_to_listicle_form(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('test-image-de.jpg', 1920, 1080);

        $component = Livewire::test(CreateListicle::class)
            ->fillForm([
                'image_de' => $file,
            ]);

        // Verify that the image field accepts the file without errors
        $component->assertFormFieldExists('image_de');
        $component->assertHasNoErrors(['image_de']);
    }

    public function test_can_upload_image_en_to_listicle_form(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('test-image-en.jpg', 1920, 1080);

        $component = Livewire::test(CreateListicle::class)
            ->fillForm([
                'image_en' => $file,
            ]);

        // Verify that the image field accepts the file without errors
        $component->assertFormFieldExists('image_en');
        $component->assertHasNoErrors(['image_en']);
    }

    public function test_can_render_create_listicle_page(): void
    {
        $this->get(ListicleResource::getUrl('create'))
            ->assertSuccessful();
    }

    public function test_can_render_edit_listicle_page(): void
    {
        $listicle = Listicle::factory()->create();

        $this->get(ListicleResource::getUrl('edit', ['record' => $listicle]))
            ->assertSuccessful();
    }
}
