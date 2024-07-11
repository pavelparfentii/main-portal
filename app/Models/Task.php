<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $guarded = false;

    protected $hidden = ['created_at', 'updated_at'];

    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'account_task')->withPivot('is_done')->withTimestamps();
    }

    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function children()
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'tag_task')->withTimestamps();
    }
}
