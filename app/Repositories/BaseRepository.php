<?php

namespace App\Repositories;

use App\Support\Sort;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(array $relations = [])
    {
        return $this->model->with($relations)->get();
    }

    public function allSorted(array $allowed, string $default = 'id', string $defaultDirection = 'asc', array $relations = [])
    {
        return Sort::apply($this->model->with($relations), $allowed, $default, $defaultDirection)->get();
    }

    public function paginate(int $perPage = 15, array $relations = [])
    {
        return $this->model->with($relations)->paginate($perPage);
    }

    public function paginateSorted(array $allowed, int $perPage = 15, array $relations = [], string $default = 'created_at', string $defaultDirection = 'desc')
    {
        return Sort::apply($this->model->with($relations), $allowed, $default, $defaultDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id, array $relations = [])
    {
        return $this->model->with($relations)->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function findBy(string $field, mixed $value, array $relations = [])
    {
        return $this->model->with($relations)->where($field, $value)->get();
    }

    public function findFirstBy(string $field, mixed $value, array $relations = [])
    {
        return $this->model->with($relations)->where($field, $value)->first();
    }

    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Return a fresh query builder on the model for chaining (with, where, orderBy, etc.).
     */
    public function query()
    {
        return $this->model->newQuery();
    }
}
