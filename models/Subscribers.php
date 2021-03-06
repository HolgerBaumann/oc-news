<?php namespace HolgerBaumann\News\Models;

use Model;

class Subscribers extends Model
{
    use \October\Rain\Database\Traits\Validation;

    protected $table = 'holgerbaumann_news_subscribers';

    public $rules = [
        'email'  => 'required|email',
        'status' => 'required|between:1,2|numeric'
    ];

    public $belongsToMany = [
        'categories' => [
            'HolgerBaumann\News\Models\Categories',
            'table' => 'holgerbaumann_news_relations',
            'key'   => 'subscriber_id',
            'order' => 'name'
        ]
    ];

    public $hasMany = [
        'logs' => [
            'HolgerBaumann\News\Models\Logs',
            'key' => 'subscriber_id'
        ],
        'logs_queued_count' => [
            'HolgerBaumann\News\Models\Logs',
            'key'   => 'subscriber_id',
            'scope' => 'isQueued',
            'count' => true
        ],
        'logs_send_count' => [
            'HolgerBaumann\News\Models\Logs',
            'key'   => 'subscriber_id',
            'scope' => 'isSend',
            'count' => true
        ],
        'logs_viewed_count' => [
            'HolgerBaumann\News\Models\Logs',
            'key'   => 'subscriber_id',
            'scope' => 'isViewed',
            'count' => true
        ],
        'logs_clicked_count' => [
            'HolgerBaumann\News\Models\Logs',
            'key'   => 'subscriber_id',
            'scope' => 'isClicked',
            'count' => true
        ],
        'logs_failed_count' => [
            'HolgerBaumann\News\Models\Logs',
            'key'   => 'subscriber_id',
            'scope' => 'isFailed',
            'count' => true
        ]
    ];

    public function beforeCreate()
    {
        $this->created = 1;
        $this->statistics = 0;
    }

    public function beforeUpdate()
    {
        unset($this->created, $this->statistics);
    }

    public function isActive()
    {
        return $this->status == 1;
    }

    public function isUnsubscribed()
    {
        return $this->status == 2;
    }

    public function activate()
    {
        $this->status = 1;
        $this->save();
    }

    public function unsubscribe()
    {
        $this->status = 2;
        $this->save();
    }

	public function deleteSubscriber()
	{
		$this->destroy($this->id);
	}

    public function scopeFilterCategories($query, $categories)
    {
        return $query->whereHas('categories', function($q) use ($categories) {
            $q->whereIn('id', $categories);
        });
    }

    public function scopeEmail($query, $email)
    {
        return $query->where('email', $email);
    }


	public function scopeKey($query, $key)
	{
		return $query->where('unsubscription_key', $key);
	}

    public function scopeIsSubscribed($query)
    {
        return $query->where('status', 1);
    }

    public function scopeIsUnsubscribed($query)
    {
        return $query->where('status', 2);
    }
}
