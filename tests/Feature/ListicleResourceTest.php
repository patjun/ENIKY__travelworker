<?php

namespace Tests\Feature;

use App\Filament\Resources\ListicleResource;
use App\Filament\Resources\ListicleResource\Pages\CreateListicle;
use App\Filament\Resources\ListicleResource\Pages\EditListicle;
use App\Models\Listicle;
use App\Models\User;
use Filament\Forms\Components\RichEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListicleResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Authenticate as a user for Filament access
        $this->actingAs(User::factory()->create());
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
