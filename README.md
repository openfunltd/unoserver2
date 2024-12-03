> docker build . -t unoserver2
> docker run --detach --restart always --publish 0.0.0.0:42604:80 \
  --mount type=bind,source="$(pwd)",target=/srv/web,readonly \
  unoserver2
