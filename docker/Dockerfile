FROM webdevops/php-dev:7.4

# Optional args
ARG BUILD_DEPS=1
ARG APP_USER="application"
ARG PATH_ROOT="/app"
ARG EXTRA_TOOLS=""

# Environment variables
ENV APP_USER ${APP_USER}
ENV PATH_ROOT ${PATH_ROOT}
ENV DEBIAN_FRONTEND "noninteractive"

RUN apt-get update -y && apt-get upgrade -y && apt-get install -y curl nano sudo libyaml-dev ${EXTRA_TOOLS}

RUN pecl install yaml

RUN echo "extension=yaml.so" >> /opt/docker/etc/php/php.ini

RUN mkdir -p ${PATH_ROOT} && chown -R ${APP_USER}:${APP_USER} ${PATH_ROOT}

RUN echo "${APP_USER}   ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

USER ${APP_USER}

WORKDIR ${PATH_ROOT}

COPY --chown=${APP_USER}:${APP_USER} . .

RUN if [ ${BUILD_DEPS} -eq 1 ]; then composer install -o --no-suggest --no-interaction --no-progress --ansi; fi

RUN if [ ${BUILD_DEPS} -eq 1 ]; then chmod +x vendor/bin/*; fi
