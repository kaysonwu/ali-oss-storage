<?php
namespace Junliuxian\AliOSS;
use OSS\OssClient;
use Junliuxian\AliOSS\Plugins\PutFile;
use Junliuxian\AliOSS\Plugins\PutRemoteFile;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;

class AliOssServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('oss', function($app, $config) {

            $cname      = isset($config['domain']) ? $config['domain'] : '';
            $endpoint   = $cname?:(isset($config['endpoint_internal'])?$config['endpoint_internal']:'');
            $client     = new OssClient($config['access_id'], $config['access_key'], ($endpoint?:$config['endpoint']), (bool)($cname));

            $adapter    = new AliOssAdapter($client, $config);
            $filesystem = new Filesystem($adapter);

            $filesystem->addPlugin(new PutFile());
            $filesystem->addPlugin(new PutRemoteFile());

            return $filesystem;
        });
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {

    }
}