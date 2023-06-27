<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Producto extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'productos';
    public function categoria_productos()
    {
        // Una categoria pertenece a un producto
        return $this->belongsTo(CategoriaProducto::class);
    }
    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'proveedor',
        'precio',
        'stock',
        'unidades_caja',
        'categoria_productos_id'
    ];

    public function pedido() {
        return $this->belongsTo(Pedido::class);
    }
}
