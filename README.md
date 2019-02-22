# swoole-biny
use swoole to expedite Biny

使用swoole配合nginx可以实现biny框架的加速；

目前压测结果是比原来使用fpm提高1.5倍左右；

用法：

(1)
```
$ git clone https://github.com/ykabps1314/swoole-biny.git
```

(2) 进入项目根目录执行
```
$ php init
```

(3) 进入server目录进行服务配置，修改swoole.config.php进行监控ip和端口的配置，以及swoole静态目录的指向配置；

(4) 启动swoole服务
```
$ php /路径/swoole-biny/server/HttpServer.php
```
线上使用守护进程方式, 可以命令指定也可以配置swoole的配置项以守护进程运行；

(5) 配置nginx

```
server {
    listen       80;
    listen       443 ssl;
    server_name  xx.xxxxx.com;
    root         /路径/swoole-biny/web;

    ssl_certificate /路径/xxxxxxxxx.crt;
    ssl_certificate_key /路径/xxxxxxxx.key;

    charset utf-8;

    location ~ /.well-known {
       allow all;
    }

    location ~ /\. {
       deny all;
    }
    location ~ .*\.(js|css|gif|jpg|jpeg|png|bmp|swf|html)?$ {
       if (!-e $request_filename) {
           proxy_pass http://127.0.0.1:9501;
       }
       break;
     }

     location / {
        proxy_http_version 1.1;
        proxy_set_header Connection "keep-alive";
        proxy_set_header X-Real-IP $remote_addr;
        if ($uri = /) {
         proxy_pass http://127.0.0.1:9501;
        }
        if (!-e $request_filename) {
             rewrite ^(.*)$ /index.php?s=$1;
             proxy_pass http://127.0.0.1:9501;
             break;
        }
      }
}

```

(6) 重启nginx后就可以访问了；

注意：
当有非框架预先在swoole中加载的资源改动时直接发布不用重启swoole服务；

但是一旦修改的文件比如框架配置文件或是依赖什么的就需要进行服务的重启，但是现实操作肯定不能直接取kill掉再重启swoole服务，所以此时提供了下面方式进行热重启；
```
$ sh /路径/swoole-biny/server/reload.sh
```

如有转载请署名来源！
