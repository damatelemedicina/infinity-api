<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseModel extends Model
{
    use HasFactory;

    /**
     * @var \Illuminate\Database\Query\Builder|null
     */
    static $recordsTotalQuery;

    static function upsert($array)
    {
        $tipo = Self::where('id', $array['id'])->first();
        if ($tipo) {
            foreach ($array as $key => $value) {
                $tipo->{$key} = $value;
            }
            $tipo->save();
            return;
        }
        Self::insert($array);
    }

    protected static function serverProcessingBase($query, array $whereColumns, array $orderColumns = [])
    {
        if (!($query instanceof \Illuminate\Database\Query\Builder) && !($query instanceof \Illuminate\Database\Eloquent\Builder)) {
            throw new \Exception('Query must be an instance of Illuminate\Database\Query\Builder or Illuminate\Database\Eloquent\Builder');
        }
        $request = request()->input('body');
        $columns = $request['columns'];
        $order = $request['order'];
        $start = $request['start'];
        $length = $request['length'];
        $search = $request['search'];

        $recordsFilteredHash = $request['recordsFilteredHash'] ?? '';
        $recordsFiltered = $request['recordsFiltered'] ?? '';
        $recordsTotal = $request['recordsTotal'] ?? '';

        $searchColumnValue = array_column(array_column($columns, 'search'), 'value');

        if (empty($recordsTotal) === true) {
            if (empty(self::$recordsTotalQuery) === false) {
                $recordsTotal = self::$recordsTotalQuery->get()->first()->recordsTotal ?? 0;
            } else {
                $queryTotal = $query->clone();
                $recordsTotal = $queryTotal->select(DB::raw('count(*) as recordsTotal'))->get()->first()->recordsTotal ?? 0;
            }
        }

        if (empty($search['value']) === false) {
            $query->where(function ($query) use ($search, $whereColumns) {
                foreach ($whereColumns as $column) {
                    if ($search['value'] === '" "' || $search['value'] === '""') {
                        $query->orWhereRaw("{$column} is" . ($search['value'] == '""' ? ' ' : ' not ') . "null");
                    } else {
                        $query->orWhere($column, 'like', '%' . $search['value'] . '%');
                    }
                }
            });
        }

        foreach ($searchColumnValue as $columnIdx => $value) {
            if (empty($value) === false) {
                if ($value === '" "' || $value === '""') {
                    $query->whereRaw("{$whereColumns[$columnIdx]} is" . ($value == '""' ? ' ' : ' not ') . "null");
                } else {
                    $query->where($whereColumns[$columnIdx], 'like', '%' . $value . '%');
                }
            }
        }

        $newHash = md5($search['value'] . implode('', $searchColumnValue));
        if ($newHash !== $recordsFilteredHash) {
            $recordsFilteredHash = $newHash;
            $queryCount = $query->clone();
            $recordsFiltered = $queryCount->select(DB::raw('count(*) as recordsFiltered'))->get()->first()->recordsFiltered ?? 0;
        }

        if (isset($order)) {
            foreach ($order as $itemOrder) {
                $columnIdx = $itemOrder['column'];
                $dir = $itemOrder['dir'];

                $query->orderBy($orderColumns[$columnIdx] ?? $columns[$columnIdx]['data'], $dir);
            }
        }

        if (isset($start) && $length != -1) {
            $query->limit($length)
                ->offset($start);
        }

        $isLocal = config('app.env') === 'local';

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'recordsFilteredHash' => $recordsFilteredHash,
            'data' => $query->get(),
            'sql' => ($isLocal ? $query->toSql() : ''),
            'sql_bindings' => ($isLocal ? $query->getBindings() : ''),
        ];
    }
}
