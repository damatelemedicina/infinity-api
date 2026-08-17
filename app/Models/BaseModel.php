<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use HasFactory;    $("#gerar").action({
        click: function(obj) {
            const self = this;
            self.disabled(true);
            api.post('/financeiroClientes', obj)
            .then(function (res) {
                self.disabled(false);
                updateTable(res.data);
            })
            .catch(function (error) {
                self.disabled(false);
                message.error(error)
                console.log(error.response ? error.response.data : error)
            });
        }
    });

    static function upsert($array) {
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

}
