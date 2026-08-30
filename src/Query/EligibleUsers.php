<?php

declare(strict_types=1);

namespace OutatimeIo\FilamentLoginShortcut\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use OutatimeIo\FilamentLoginShortcut\Exceptions\InvalidConfiguration;
use OutatimeIo\FilamentLoginShortcut\LoginShortcutPlugin;

final class EligibleUsers
{
    /** @return Builder<Model> */
    public function build(LoginShortcutPlugin $plugin): Builder
    {
        $model = $plugin->model();
        /** @var Builder<Model> $query */ $query = $model::query();
        if ($callback = $plugin->query()) {
            $query = app()->call($callback, ['query' => $query]);
            if (! $query instanceof Builder || $query->getModel()::class !== $model) {
                throw new InvalidConfiguration('The custom user query must return a builder for the configured user model.');
            }
        } elseif ($plugin->emails() !== null) {
            $query->whereIn('email', $plugin->emails());
        } elseif ($plugin->domains() !== null) {
            if ($plugin->domains() === []) {
                return $query->whereRaw('1 = 0');
            }
            $grammar = $query->getQuery()->getGrammar();
            $email = $grammar->wrap('email');
            $query->where(function (Builder $query) use ($email, $plugin): void {
                foreach ($plugin->domains() as $domain) {
                    $query->orWhereRaw("LOWER({$email}) LIKE ? ESCAPE CHAR(92)", ['%@'.$this->escapeLike($domain)]);
                }
            });
        }

        return $query;
    }

    /** @return Builder<Model> */
    public function matching(LoginShortcutPlugin $plugin, string $term): Builder
    {
        $query = $this->build($plugin);
        $term = $this->escapeLike(mb_strtolower($term));

        return $query->where(function (Builder $query) use ($plugin, $term): void {
            foreach ($plugin->columns() as $column) {
                $query->orWhereRaw('LOWER('.$query->getQuery()->getGrammar()->wrap($column).') LIKE ? ESCAPE CHAR(92)', ['%'.$term.'%']);
            }
        });
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
