FROM hub.qucheng.com/library/debian:11.3-slim

LABEL maintainer "zhouyueqiu <zhouyueqiu@easycorp.ltd>"

ENV OS_ARCH="amd64" \
    OS_NAME="debian-11" \
    HOME_PAGE="www.xuanim.com"

COPY debian/prebuildfs /

ENV TZ=Asia/Shanghai \
    DEBIAN_FRONTEND=noninteractive

RUN sed -i -r 's/(deb|security).debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list \
    && install_packages curl wget tzdata zip unzip s6 pwgen cron procps \
    && ln -fs /usr/share/zoneinfo/${TZ} /etc/localtime \
    && echo ${TZ} > /etc/timezone \
    && dpkg-reconfigure --frontend noninteractive tzdata

# Install xuanxuan
ARG VERSION
ENV XUANXUAN_VER=${VERSION}
ENV EASYSOFT_APP_NAME="xuanxuan $XUANXUAN_VER"

# Install render-template
RUN . /opt/easysoft/scripts/libcomponent.sh && component_unpack "render-template" "1.0.1-10" --checksum 5e410e55497aa79a6a0c5408b69ad4247d31098bdb0853449f96197180ed65a4

# Install wait-for-port
RUN . /opt/easysoft/scripts/libcomponent.sh && component_unpack "wait-for-port" "1.01" -c 2ad97310f0ecfbfac13480cabf3691238fdb3759289380262eb95f8660ebb8d1

# Download xuanxuan
RUN . /opt/easysoft/scripts/libcomponent.sh && z_download "xuanxuan" "${XUANXUAN_VER}"

# Copy apache,php and gogs config files
COPY debian/rootfs /

WORKDIR /opt/zbox
RUN chown nobody.nogroup . -R

EXPOSE 80 11444 11443

# Persistence directory
VOLUME [ "/data"]

ENTRYPOINT ["/usr/bin/entrypoint.sh"]
