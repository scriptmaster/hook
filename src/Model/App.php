<?php namespace Hook\Model;

use Hook\Database\CollectionDelegator;
use Hook\Application\Context;
use Hook\Http\Router;

/**
 * App
 */
class App extends Model
{

    public static function boot()
    {
        parent::boot();
        static::creating(function ($instance) { $instance->beforeCreate(); });
        static::created(function ($instance) { $instance->afterCreate(); });
    }

    /**
     * currentId
     * @static
     * @return int
     */
    public static function currentId()
    {
        return Context::getKey()->app_id;
    }

    /**
     * collection
     * @static
     * @param  mixed                        $name name
     * @return Database\CollectionDelegator
     */
    public static function collection($name)
    {
        return new CollectionDelegator($name, static::currentId());
    }

    /**
     * url
     * @static
     * @param  string                       $segments segments
     * @param  array                        $options options
     * @return Database\CollectionDelegator
     */
    public static function url($segments, $options = array())
    {
        $request = Router::getInstance()->request;

        $options['X-App-Id'] = Context::getKey()->app_id;
        $options['X-App-Key'] = Context::getKey()->key;

        $segments .= '?' . http_build_query($options);

        return $request->getUrl() . $request->getScriptName() . '/' . $segments;
    }

    public function keys()
    {
        return $this->hasMany('Hook\Model\AppKey', 'app_id');
    }

    public function modules()
    {
        return $this->hasMany('Hook\Model\Module', 'app_id');
    }

    public function beforeCreate()
    {
        // Generate app secret.
        $this->secret = md5(uniqid(rand(), true));
    }

    public function afterCreate()
    {
        // Generate commandline (full-access)
        $this->keys()->create(array('type' => AppKey::TYPE_CLI));

        // Generate browser key (client-side)
        $this->keys()->create(array('type' => AppKey::TYPE_BROWSER));

        // Generate device key  (client-side)
        $this->keys()->create(array('type' => AppKey::TYPE_DEVICE));

        // Generate server key  (server-side)
        $this->keys()->create(array('type' => AppKey::TYPE_SERVER));

        // Create storage directory for this app
        $storage_dir = storage_dir(true, $this->_id);

        // maybe we're on a readonly filesystem
        if (!file_exists($storage_dir) && is_writable(dirname($storage_dir))) {
            mkdir($storage_dir, 0777, true);
            file_put_contents($storage_dir . 'logs.txt', '');
        }
    }

    public function toArray()
    {
        $arr = parent::toArray();
        $arr['keys'] = $this->keys->toArray();

        return $arr;
    }

}
