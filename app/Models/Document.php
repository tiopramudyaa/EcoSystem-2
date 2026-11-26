<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory, Concerns\TouchesProjectActivity;

    protected $fillable = [
        'project_id',
        'folder_id',
        'document_name',
        'link_document',
        'onedrive_item_id',
        'document_type',
    ];

    public function delivery_project()
    {
        return $this->belongsTo(DeliveryProject::class, 'delivery_projects_id');
    }

    public function folder()
    {
        return $this->belongsTo(DeliveryProjectDocumentFolder::class, 'folder_id');
    }
}