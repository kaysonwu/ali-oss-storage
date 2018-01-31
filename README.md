# Summary
[中文说明](https://github.com/junliuxian/ali-oss-storage/blob/master/README_zh-CN.md)  

This project is [jacobcyl/Aliyun-oss-storage 2.0.4](https://github.com/jacobcyl/Aliyun-oss-storage) optimized derivative version.
Let it support `Lumen` well.  

# Install

- Use `composer` to quickly install
    
    - php 7.0 >=
    ```
    composer require junliuxian/ali-oss-storage:~2.0
   ```
        
    - php 5.5 >= 
    ```
    composer require junliuxian/ali-oss-storage:~1.0
    ```

- in your `config/filesystems.php` copy `config/oss.php` to  `disks` array:

    ```
    'disks' => [
        // ... 
        
        'oss'   => [
            // config/oss.php
        ]
    ]
    ```

- Then set the default driver in `config/filesystems.php`
    ```
    'default' => 'oss'
    ```

- Registration service provider

    - `laravel`: in your `config/app.php` add this line to `providers` array:
    
        ```
        'providers' => [
            // ... 
            Junliuxian\AliOSS\AliOssServiceProvider::class,
        ]
        ```
        
    - `lumen`: in your `bootstrap/app.php` add this line
    
        ```
        $app->register(Junliuxian\AliOSS\AliOssServiceProvider::class);
        ```

# Config
name | type | description
---|---|---
access_id | string | Aliyun OSS AccessKeyId
access_key | string | Aliyun OSS AccessKeySecret
bucket | string | Aliyun OSS bucket name
endpoint | string | Aliyun OSS extranet node or custom external domain name
endpoint_internal | string | Aliyun OSS intranet node
prefix | string | path prefix
domain | string | Custom domain name binding
ssl | boolean | enabled or disabled SSL
debug | boolean | Whether to open Debug mode

# API

```
Storage::disk('oss'); // if default filesystems driver is oss, you can skip this step

//fetch all files of specified bucket(see upond configuration)
Storage::files($directory);
Storage::allFiles($directory);

Storage::put('path/to/file/file.jpg', $contents); //first parameter is the target file path, second paramter is file content
Storage::putFile('path/to/file/file.jpg', 'local/path/to/local_file.jpg'); // upload file from local path

Storage::get('path/to/file/file.jpg'); // get the file object by path
Storage::exists('path/to/file/file.jpg'); // determine if a given file exists on the storage(OSS)
Storage::size('path/to/file/file.jpg'); // get the file size (Byte)
Storage::lastModified('path/to/file/file.jpg'); // get date of last modification

Storage::directories($directory); // Get all of the directories within a given directory
Storage::allDirectories($directory); // Get all (recursive) of the directories within a given directory

Storage::copy('old/file1.jpg', 'new/file1.jpg');
Storage::move('old/file1.jpg', 'new/file1.jpg');
Storage::rename('path/to/file1.jpg', 'path/to/file2.jpg');

Storage::prepend('file.log', 'Prepended Text'); // Prepend to a file.
Storage::append('file.log', 'Appended Text'); // Append to a file.

Storage::delete('file.jpg');
Storage::delete(['file1.jpg', 'file2.jpg']);

Storage::makeDirectory($directory); // Create a directory.
Storage::deleteDirectory($directory); // Recursively delete a directory.It will delete all files within a given directory, SO Use with caution please.

// upgrade logs
// new plugin for v2.0 version
Storage::putRemoteFile('target/path/to/file/jacob.jpg', 'http://example.com/jacob.jpg'); //upload remote file to storage by remote url
// new function for v2.0.1 version
Storage::url('path/to/img.jpg') // get the file url
```

# Documentation
More development detail see [Aliyun OSS DOC](https://help.aliyun.com/document_detail/32099.html?spm=5176.doc31981.6.335.eqQ9dM)

# License
Source code is release under MIT license. Read LICENSE file for more information.
