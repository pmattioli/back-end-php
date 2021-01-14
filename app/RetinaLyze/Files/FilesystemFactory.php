<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Files;

use RetinaLyze\Utils\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;

/**
 * Description of FileManager
 *
 * @author mom
 */
class FilesystemFactory {
    
    public static function create($path, $filesystem, $regionOverride = null) {
        $config = Config::getConfig();
        $region = empty($regionOverride) ? $config['aws_region'] : $regionOverride;
        switch ($filesystem) {
            case "local":
                $adapter = new Local($path);
                break;
            case "s3":
                if(!empty($config['s3_key'])){
                    $credential = array(
                        'key'    => $config['s3_key'],
                        'secret' => $config['s3_secret']);
                }else{
                    $credential = null;
                }
                $client = S3Client::factory([
                    'credentials' => $credential,
                    'region' => $region,
                    'version' => 'latest',
                ]);
                $adapter = new AwsS3Adapter($client, $path);
                break;
            default:
                $adapter = new Local($path);
                break;
        }
        return new Filesystem($adapter, new \League\Flysystem\Config(['disable_asserts' => true,]));
    }
}
