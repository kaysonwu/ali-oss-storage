# 说明
本项目是 [jacobcyl/Aliyun-oss-storage 2.0.4](https://github.com/jacobcyl/Aliyun-oss-storage)的优化衍生版本。
让其能够很好的支持 `Lumen`。

# 安装

- 使用 `composer` 快速安装

    - php 7.0 >=
    ```
    composer require junliuxian/ali-oss-storage:~2.0
   ```
        
    - php 5.5 >= 
    ```
    composer require junliuxian/ali-oss-storage:~1.0
    ```

- 复制 `config/oss.php` 配置到你的 `config/filesystems.php` 文件的 `disks` 数组中:

    ```
    'disks' => [
        // ... 
        
        'oss'   => [
            // config/oss.php
        ]
    ]
    ```

- 然后在 `config/filesystems.php` 中设置默认的驱动程序
    ```
    'default' => 'oss'
    ```

- 注册服务提供者

    - `laravel`: 在 `config/app.php` 文件中添加这一行到 `providers` 数组中:
    
        ```
        'providers' => [
            // ... 
            Junliuxian\AliOSS\AliOssServiceProvider::class,
        ]
        ```
        
    - `lumen`: 在 `bootstrap/app.php` 文件中添加这一行
    
        ```
        $app->register(Junliuxian\AliOSS\AliOssServiceProvider::class);
        ```

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

# 文档
更多的开发细节请参考 [Aliyun OSS DOC](https://help.aliyun.com/document_detail/32099.html?spm=5176.doc31981.6.335.eqQ9dM)

# 许可协议
源代码是在 `MIT` 许可下发布的。 阅读 `LICENSE` 文件以获取更多信息。