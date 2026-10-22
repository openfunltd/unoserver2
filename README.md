> docker build --pull . -t unoserver2
> docker run --detach --restart always --name unoserver2 --publish 0.0.0.0:42604:80 \
  --mount type=bind,source="$(pwd)",target=/srv/web,readonly \
  unoserver2

PHP 版本寫死在 `Dockerfile` 的 `FROM`，有資安更新時改這裡、重 build 並重建 container（`docker restart` 不會換 image），
驗證：`docker exec unoserver2 php -r 'echo PHP_VERSION;'`
