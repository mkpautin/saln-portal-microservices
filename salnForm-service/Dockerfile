# syntax=docker/dockerfile:1

ARG BREF_IMAGE=bref/php-84:3
ARG CORRETTO_IMAGE=amazoncorretto:17
ARG PDFTK_JAVA_URL="https://gitlab.com/api/v4/projects/5024297/packages/generic/pdftk-java/v3.3.3/pdftk-all.jar"

FROM curlimages/curl:8.12.1 AS pdftk-java
ARG PDFTK_JAVA_URL
RUN curl -fsSL --retry 3 --retry-delay 2 -o /tmp/pdftk-all.jar "${PDFTK_JAVA_URL}"

FROM ${CORRETTO_IMAGE} AS java-runtime
RUN jlink \
    --add-modules java.base,java.desktop,java.logging,java.naming,java.xml,jdk.crypto.ec \
    --no-man-pages \
    --no-header-files \
    --compress=2 \
    --output /opt/java

FROM ${BREF_IMAGE}

COPY --from=java-runtime /opt/java /opt/java
COPY --from=pdftk-java /tmp/pdftk-all.jar /opt/pdftk/pdftk-all.jar

RUN printf '%s\n' \
    '#!/bin/sh' \
    'exec /opt/java/bin/java -jar /opt/pdftk/pdftk-all.jar "$@"' \
    > /usr/local/bin/pdftk \
    && chmod +x /usr/local/bin/pdftk

COPY . /var/task

ENV BREF_RUNTIME=fpm
ENV PDFTK_BINARY=/usr/local/bin/pdftk
ENV PATH="/usr/local/bin:/opt/java/bin:${PATH}"

CMD ["public/index.php"]
