<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Tag\Actions\CreateAlias;
use Modules\Tag\Actions\CreateImplication;
use Modules\Tag\Actions\ImportTaxonomy;
use Modules\Tag\DataObjects\TaxonomyDocument;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;
use Modules\Tag\Services\TaxonomyExporter;
use Modules\User\Models\User;
use Tests\TestCase;

final class TaxonomyTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_carries_categories_tags_aliases_and_implications(): void
    {
        $character = TagCategory::factory()->create(['name' => 'character']);
        $calico = Tag::factory()->create(['name' => 'calico', 'description' => 'Tricolour cat.']);
        $cat = Tag::factory()->for($character, 'category')->create(['name' => 'cat']);
        app(CreateAlias::class)->handle($cat, 'kitty');
        app(CreateImplication::class)->handle($calico, $cat);

        $document = app(TaxonomyExporter::class)->export()->toArray();

        $this->assertSame(1, $document['version']);
        $this->assertContains(['alias' => 'kitty', 'tag' => 'cat'], $document['aliases']);
        $this->assertContains(['tag' => 'calico', 'implies' => 'cat'], $document['implications']);
        $this->assertContains(
            ['name' => 'calico', 'category' => 'general', 'description' => 'Tricolour cat.'],
            $document['tags'],
        );
    }

    public function test_import_is_additive_and_round_trips(): void
    {
        $document = TaxonomyDocument::fromArray([
            'version' => 1,
            'categories' => [['name' => 'character', 'color' => '#00aa00', 'sort_order' => 1]],
            'tags' => [
                ['name' => 'cat', 'category' => 'character', 'description' => 'Domestic cat.'],
                ['name' => 'calico', 'category' => 'general', 'description' => null],
            ],
            'aliases' => [['alias' => 'kitty', 'tag' => 'cat']],
            'implications' => [['tag' => 'calico', 'implies' => 'cat']],
        ]);

        $conflicts = app(ImportTaxonomy::class)->handle($document, false);

        $this->assertSame([], $conflicts);
        $this->assertSame('character', Tag::query()->where('name', 'cat')->firstOrFail()->category?->name);
        $this->assertTrue(Tag::query()->where('name', 'calico')->firstOrFail()->implications()->where('name', 'cat')->exists());
    }

    public function test_import_reports_conflicts_instead_of_skipping_silently(): void
    {
        $cat = Tag::factory()->create(['name' => 'cat']);
        app(CreateAlias::class)->handle($cat, 'kitty');

        $document = TaxonomyDocument::fromArray([
            'version' => 1,
            'categories' => [],
            'tags' => [['name' => 'dog', 'category' => 'general', 'description' => null]],
            'aliases' => [['alias' => 'kitty', 'tag' => 'dog']],
            'implications' => [],
        ]);

        $conflicts = app(ImportTaxonomy::class)->handle($document, false);

        $this->assertCount(1, $conflicts);
    }

    public function test_import_rejects_a_cycle(): void
    {
        $document = TaxonomyDocument::fromArray([
            'version' => 1,
            'categories' => [],
            'tags' => [
                ['name' => 'a', 'category' => 'general', 'description' => null],
                ['name' => 'b', 'category' => 'general', 'description' => null],
            ],
            'aliases' => [],
            'implications' => [
                ['tag' => 'a', 'implies' => 'b'],
                ['tag' => 'b', 'implies' => 'a'],
            ],
        ]);

        $this->assertCount(1, app(ImportTaxonomy::class)->handle($document, false));
    }

    public function test_only_an_administrator_may_import(): void
    {
        $file = UploadedFile::fake()->createWithContent('taxonomy.json', json_encode([
            'version' => 1,
            'categories' => [],
            'tags' => [],
            'aliases' => [],
            'implications' => [],
        ]));

        $this->actingAs(User::factory()->moderator()->create())
            ->post('/admin/tags/taxonomy', ['file' => $file])
            ->assertForbidden();
    }
}
