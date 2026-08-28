<?php

declare(strict_types=1);

namespace Modules\Tag\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tag\Models\Tag;
use Modules\Tag\Services\ImplicationClosureResolver;
use Tests\TestCase;

final class ImplicationClosureResolverTest extends TestCase
{
    use RefreshDatabase;

    private ImplicationClosureResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(ImplicationClosureResolver::class);
    }

    public function test_it_follows_a_chain_transitively(): void
    {
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        $animal = Tag::factory()->create(['name' => 'animal']);

        $calico->implications()->attach($cat->id);
        $cat->implications()->attach($animal->id);

        $closure = $this->resolver->expand([$calico->id]);

        sort($closure);
        $expected = [$cat->id, $animal->id];
        sort($expected);

        $this->assertSame($expected, $closure);
    }

    public function test_it_excludes_the_input_tags_from_the_result(): void
    {
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        $calico->implications()->attach($cat->id);

        $this->assertSame([$cat->id], $this->resolver->expand([$calico->id, $cat->id]));
    }

    public function test_it_merges_diamond_paths_without_duplicates(): void
    {
        $tortie = Tag::factory()->create(['name' => 'tortie']);
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);

        $tortie->implications()->attach($cat->id);
        $calico->implications()->attach($cat->id);

        $this->assertSame([$cat->id], $this->resolver->expand([$tortie->id, $calico->id]));
    }

    public function test_it_returns_nothing_for_an_empty_input(): void
    {
        $this->assertSame([], $this->resolver->expand([]));
    }

    public function test_reaches_reports_transitive_reachability(): void
    {
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        $animal = Tag::factory()->create(['name' => 'animal']);

        $calico->implications()->attach($cat->id);
        $cat->implications()->attach($animal->id);

        $this->assertTrue($this->resolver->reaches($calico->id, $animal->id));
        $this->assertFalse($this->resolver->reaches($animal->id, $calico->id));
    }

    public function test_ancestors_walks_upward(): void
    {
        $calico = Tag::factory()->create(['name' => 'calico']);
        $cat = Tag::factory()->create(['name' => 'cat']);
        $animal = Tag::factory()->create(['name' => 'animal']);

        $calico->implications()->attach($cat->id);
        $cat->implications()->attach($animal->id);

        $ancestors = $this->resolver->ancestors($animal->id);
        sort($ancestors);
        $expected = [$calico->id, $cat->id];
        sort($expected);

        $this->assertSame($expected, $ancestors);
    }

    public function test_it_terminates_on_a_cycle_written_directly_to_the_database(): void
    {
        // Cycles cannot be created through CreateImplication, but a hand-edited
        // database or a bad import must not hang the resolver.
        $a = Tag::factory()->create(['name' => 'a']);
        $b = Tag::factory()->create(['name' => 'b']);

        $a->implications()->attach($b->id);
        $b->implications()->attach($a->id);

        $closure = $this->resolver->expand([$a->id]);

        $this->assertContains($b->id, $closure);
        $this->assertNotContains($a->id, $closure);
    }

    public function test_seed_with_self_cycle_is_excluded_regardless_of_argument_order(): void
    {
        // Regression test: expand() must be set-like (order-independent).
        // Seed x implies unrelated tag a; seed y has self-cycle (y→v→y).
        // Both orderings should exclude y and return [a, v].
        $x = Tag::factory()->create(['name' => 'x']);
        $a = Tag::factory()->create(['name' => 'a']);
        $y = Tag::factory()->create(['name' => 'y']);
        $v = Tag::factory()->create(['name' => 'v']);

        $x->implications()->attach($a->id);
        $y->implications()->attach($v->id);
        $v->implications()->attach($y->id);

        $closure1 = $this->resolver->expand([$x->id, $y->id]);
        sort($closure1);
        $expected = [$a->id, $v->id];
        sort($expected);

        $this->assertSame($expected, $closure1);

        // Reverse order should yield the same result.
        $closure2 = $this->resolver->expand([$y->id, $x->id]);
        sort($closure2);

        $this->assertSame($expected, $closure2);
    }
}
