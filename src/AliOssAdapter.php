<?php
namespace Junliuxian\AliOSS;
use OSS\OssClient;
use OSS\Core\OssException;
use League\Flysystem\Config;
use League\Flysystem\Util;
use League\Flysystem\Adapter\AbstractAdapter;

class AliOssAdapter extends AbstractAdapter
{
    /**
     * Result key name mapping
     *
     * @var array
     */
    protected static $resultMap = [
        'Body'           => 'raw_contents',
        'Content-Length' => 'size',
        'ContentType'    => 'mimetype',
        'Size'           => 'size',
        'StorageClass'   => 'storage_class',
    ];

    /**
     * File meta information alias
     *
     * @var array
     */
    protected static $metas = [
        'CacheControl'         => 'Cache-Control',
        'Expires'              => 'Expires',
        'ServerSideEncryption' => 'x-oss-server-side-encryption',
        'Metadata'             => 'x-oss-metadata-directive',
        'ACL'                  => 'x-oss-object-acl',
        'ContentType'          => 'Content-Type',
        'ContentDisposition'   => 'Content-Disposition',
        'ContentLanguage'      => 'response-content-language',
        'ContentEncoding'      => 'Content-Encoding',
    ];

    /**
     * Aliyun OSS Client
     *
     * @var OssClient
     */
    protected $client;

    /**
     * File meta information
     *
     * @var array
     */
    protected $options = [
        'Multipart'   => 128
    ];

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @var string
     */
    protected $scheme;

    /**
     * @var string
     */
    protected $host;

    /**
     * Debug mode
     *
     * @var bool
     */
    protected $debug;

    /**
     * AliOssAdapter constructor.
     *
     * @param OssClient $client
     * @param array $config
     * @param array $options
     */
    public function __construct(OssClient $client, array $config, array $options = [])
    {
        $this->client  = $client;
        $this->bucket  = $config['bucket'];

        $this->scheme  = (isset($config['ssl']) && $config['ssl']) ? 'https://' : 'http://';
        $this->host    = (isset($config['domain']) && $config['domain']) ? $config['domain'] : $this->bucket.$config['endpoint'];

        $this->debug   = isset($config['debug']) && $config['debug'];
        $this->options = array_merge($this->options, $options);

        if (isset($config['prefix']))
            $this->setPathPrefix($config['prefix']);
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        $path    = $this->applyPathPrefix($path);
        $options = $this->getOptions($config);

        if (!isset($options[OssClient::OSS_LENGTH])) {
            $options[OssClient::OSS_LENGTH] = Util::contentSize($contents);
        }

        if (!isset($options[OssClient::OSS_CONTENT_TYPE])) {
            $options[OssClient::OSS_CONTENT_TYPE] = Util::guessMimeType($path, $contents);
        }

        try {
            $this->client->putObject($this->bucket, $path, $contents, $options);
        } catch (OssException $e) {
            $this->debug($e);
            return false;
        }

        return $this->normalizeResponse($options, $path);
    }

    /**
     * Get options for a OSS call. done
     *
     * @param Config $config
     * @param array  $options
     * @return array OSS options
     */
    protected function getOptions(Config $config = null, array $options = [])
    {
        return [OssClient::OSS_HEADERS=>array_merge($this->options, $options, $this->getOptionsFromConfig($config))];
    }

    /**
     * Retrieve options from a Config instance. done
     *
     * @param Config $config
     * @return array
     */
    protected function getOptionsFromConfig(Config $config)
    {
        if (empty($config))
            return array();

        $options = [];
        foreach (static::$metas as $key=>$meta) {

            if ($config->has($key)) {
                $options[$meta] = $config->get($key);
            }
        }

        if ($visibility = $config->get('visibility')) {
            // For local reference
            // $options['visibility'] = $visibility;
            // For external reference
            $options['x-oss-object-acl'] = $visibility === static::VISIBILITY_PUBLIC
                                            ? OssClient::OSS_ACL_TYPE_PUBLIC_READ
                                            : OssClient::OSS_ACL_TYPE_PRIVATE;
        }

        if ($mimetype = $config->get('mimetype')) {
            // For local reference
            // $options['mimetype'] = $mimetype;
            // For external reference
            $options['Content-Type'] = $mimetype;
        }

        return $options;
    }

    /**
     * Normalize a result from OSS.
     *
     * @param array  $options
     * @param string $path
     * @return array file metadata
     */
    protected function normalizeResponse(array $options, $path = null)
    {
        if (!$path){
            $path = $this->removePathPrefix(isset($options['Key']) ? $options['Key'] : $options['Prefix']);
        }

        $result = ['path'=>$path, 'dirname'=> Util::dirname($path)];

        if (isset($options['LastModified'])) {
            $result['timestamp'] = strtotime($options['LastModified']);
        }

        if (substr($result['path'], -1) === '/') {
            return array_merge($result, ['type'=>'dir', 'path'=>rtrim($result['path'], '/')]);
        }

        return array_merge($result, Util::map($options, static::$resultMap), ['type'=>'file']);
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->write($path, stream_get_contents($resource), $config);
    }

    /**
     * Write a new file by local file.
     *
     * @param string $path
     * @param string $filename
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeFile($path, $filename, Config $config)
    {
        $path    = $this->applyPathPrefix($path);
        $options = $this->getOptions($config, [OssClient::OSS_CHECK_MD5=>true]);

        if (!isset($options[OssClient::OSS_CONTENT_TYPE])) {
            $options[OssClient::OSS_CONTENT_TYPE] = Util::guessMimeType($path, '');
        }

        try {
            $this->client->uploadFile($this->bucket, $path, $filename, $options);
        } catch (OssException $e) {
            $this->debug($e);
            return false;
        }

        return $this->normalizeResponse($options, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        if (!$config->has('visibility') && !$config->has('ACL')) {
            $config->set(static::$metas['ACL'], $this->getObjectACL($path));
        }

        return $this->write($path, $contents, $config);
    }

    /**
     * The the ACL visibility.
     *
     * @param string $path
     * @return string
     */
    protected function getObjectACL($path)
    {
        $metadata = $this->getVisibility($path);
        return $metadata['visibility'] === static::VISIBILITY_PUBLIC
                                           ? OssClient::OSS_ACL_TYPE_PUBLIC_READ
                                           : OssClient::OSS_ACL_TYPE_PRIVATE;
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->update($path, stream_get_contents($resource), $config);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newPath)
    {
        if (!$this->copy($path, $newPath))
            return false;

        return $this->delete($path);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        $path    = $this->applyPathPrefix($path);
        $newpath = $this->applyPathPrefix($newpath);

        try {
            $this->client->copyObject($this->bucket, $path, $this->bucket, $newpath);
        } catch (OssException $e) {
            $this->debug($e);
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        try {
            $this->client->deleteObject($this->bucket, $this->applyPathPrefix($path));
        } catch (OssException $e) {
            $this->debug($e);
            return false;
        }

        return true; // return !$this->has($path);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        $dirname = rtrim($this->applyPathPrefix($dirname), '/').'/';

        try {

            $files = array();
            $list  = $this->listDirObjects($dirname, true);

            foreach ($list as $objects) {

                /**@var \OSS\Model\ObjectInfo[] $objects */
                foreach($objects as $object){
                    $files[] = $object->getKey();
                }
            }

            if (!empty($files)) {
                $this->client->deleteObjects($this->bucket, $files);
            }

            $this->client->deleteObject($this->bucket, $dirname);

        } catch (OssException $e) {
            $this->debug($e);
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        try {

            $files = array();
            $list = $this->listDirObjects($directory, $recursive);

            foreach ($list as $objects) {
                /** @var \OSS\Model\ObjectInfo[] $objects */
                foreach($objects as $object) {
                    $files[] = $this->normalizeResponse([
                        'LastModified'  => $object->getLastModified(),
                        'eTag'          => $object->getETag(),
                        'Type'          => $object->getType(),
                        'Size'          => $object->getSize(),
                        'StorageClass'  => $object->getStorageClass(),
                    ], $object->getKey());
                }
            }

        } catch (OssException $e) {
            $this->debug($e);
            return array();
        }

        return Util::emulateDirectories($files);
    }

    /**
     * List the files in the storage space
     *
     * @param string $dirname
     * @param bool|false $recursive
     * @return array
     */
    protected function listDirObjects($dirname = '', $recursive =  false)
    {
        $list    = array();
        $options = ['delimiter'=>'/', 'prefix'=>$dirname, 'max-keys'=>1000, 'marker'=>''];

        do {

            $info  = $this->client->listObjects($this->bucket, $options);
            $dirs  = $info->getPrefixList(); // 目录列表
            $files = $info->getObjectList(); // 文件列表

            if ($recursive && $dirs) {
                foreach ($dirs as $dir) {
                    $list = array_merge($list, $this->listDirObjects($dir->getPrefix(), $recursive));
                }
            }

            if (!empty($files))
                $list[] = $files;

        }while(($options['marker'] = $info->getNextMarker()) !== '');

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        $path    = $this->applyPathPrefix($dirname);
        $options = $this->getOptionsFromConfig($config);

        try {
            $this->client->createObjectDir($this->bucket, $path, $options);
        } catch (OssException $e) {
            $this->debug($e);
            return false;
        }

        return ['path'=>$dirname, 'type'=>'dir'];
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        $path = $this->applyPathPrefix($path);
        $acl  = ($visibility === static::VISIBILITY_PUBLIC )
                                 ? OssClient::OSS_ACL_TYPE_PUBLIC_READ
                                 : OssClient::OSS_ACL_TYPE_PRIVATE;
        try {
            $this->client->putObjectAcl($this->bucket, $path, $acl);
        } catch (OssException $e) {
            $this->debug($e);
            return false;
        }

        return compact('visibility');
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        try {
            $acl = $this->client->getObjectAcl($this->bucket, $this->applyPathPrefix($path));
        } catch (OssException $e) {
            $this->debug($e);
            return false;
        }

        return ['visibility'=>($acl == OssClient::OSS_ACL_TYPE_PUBLIC_READ
                                       ? static::VISIBILITY_PUBLIC
                                       : static::VISIBILITY_PRIVATE
        )];
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        try {
            return $this->client->doesObjectExist($this->bucket, $this->applyPathPrefix($path));
        } catch (OssException $e) {
            $this->debug($e);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        if (!($result = $this->readObject($path)))
            return false;

        $result['contents'] = (string) $result['raw_contents'];
        unset($result['raw_contents']);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        if (!($result = $this->readObject($path)))
            return false;

        $result['stream'] = $result['raw_contents'];
        rewind($result['stream']);

        // Ensure the EntityBody object destruction doesn't close the stream
        $result['raw_contents']->detachStream();
        unset($result['raw_contents']);

        return $result;
    }

    /**
     * Read an object from the OssClient.
     *
     * @param string $path
     * @return array|bool
     */
    protected function readObject($path)
    {
        try {
            $body = $this->client->getObject($this->bucket, $this->applyPathPrefix($path));
        } catch (OssException $e) {
            $this->debug($e);
            return false;
        }

        return $this->normalizeResponse(['Body'=>$body, 'type'=>'file'], $path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        try {
            return $this->client->getObjectMeta($this->bucket, $this->applyPathPrefix($path));
        } catch (OssException $e) {
            $this->debug($e);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        if ($meta = $this->getMetadata($path))
            $meta['size'] = $meta['content-length'];

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        if ($meta = $this->getMetadata($path))
            $meta['mimetype'] = $meta['content-type'];

        return $meta;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        if ($meta = $this->getMetadata($path))
            $meta['timestamp'] = strtotime($meta['last-modified']);

        return $meta;
    }

    /**
     * Get the URL for the file at the given path
     *
     * @param string $path
     * @return string
     */
    public function getUrl($path)
    {
        return $this->scheme . $this->host . '/' . ltrim($path, '/');
    }


    /**
     * Debug mode
     *
     * @param \Exception $e
     * @throws \Exception
     */
    protected function debug($e)
    {
        if (!$this->debug)
            return;

        throw $e;
    }
}