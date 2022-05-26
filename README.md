# 喧喧官方Docker镜像

## 一、关于喧喧

喧喧是由ZDOO协同团队推出的一款轻量级、开放的企业协同聊天软件。

喧喧官网：https://www.xuanim.com/

**为什么来做喧喧**
我们在做ZDOO协同办公系统的时候，就在寻找轻量级、跨平台、容易开发的聊天软件解决方案。但很遗憾的是我们找了一圈都不太靠谱。有很多解决方案依赖包太多，安装部署各种问题。还有普遍的客户端聊天软件都是用传统的GUI方式编写，界面一般，二次开发困难。国内很多做聊天软件存在各种限制。

找不到合适的轮子，就自己来做一个吧。这个项目从2015年底左右就已经启动了。当时的技术方案是用的nw.js。一直是内部在测试，没有正式对外发布。今年春节过后，我们迁移到了electron平台上面，于2017年2月22日正式发布了第一个版本。

**喧喧的定位**
喧喧定位是企业内部的轻量级的聊天软件。为了让喧喧足够轻量级，客户端软件使用了html5的技术，XXD服务器端采用Go语言实现，Go语言具备高性能、支持高并发、易于学习使用。喧喧不仅支持文字和图片交流形式，还提供音视频聊天、桌面共享等功能。喧喧可以当作协同软件的一个延伸和补充。

**喧喧足够轻量级**
- 喧喧的客户端使用html5技术，和服务器端通讯使用socket，附件的上传下载使用http协议。
- 喧喧的XXD服务器端采用Go语言实现，Go语言具备高性能、支持高并发。
- 喧喧的服务器端现在和ZDOO协同绑在一起，我们也把服务器端的功能独立出来来，大家可以独立部署。
- 喧喧的附件上传下载借助webserver来避免消息的阻塞。消息存储使用mysql，表结构也非常简单。

所以说喧喧是一个已经实现基础功能的，跨平台的，轻量级的，非常容易进行二次开发的企业协同聊天解决方案！


## 二、支持的标签
- 5.6

## 三、获取镜像
推荐从 [Docker Hub Registry](https://hub.docker.com/r/easysoft/xuanxuan) 拉取我们构建好的官方Docker镜像【目前提供国内加速镜像地址】
```bash
docker pull hub.qucheng.com/app/xuanxuan:5.6
```

如需使用指定的版本，可以拉取一个包含版本标签的镜像，在Docker Hub仓库中查看 [可用版本列表](https://hub.docker.com/r/easysoft/xuanxuan/tags/) 

```bash
docker pull hub.qucheng.com/app/xuanxuan:[TAG]
```

## 四、持久化数据
如果你删除容器，所有的数据都将被删除，下次运行镜像时会重新初始化数据。为了避免数据丢失，你应该为容器提供一个挂在卷，这样可以将数据进行持久化存储。

为了数据持久化，你应该挂载持久化目录：

- /data 喧喧持久化数据

如果挂载的目录为空，首次启动会自动初始化相关文件

```bash
$ docker run -it \
    -v $PWD/data:/data \
docker pull hub.qucheng.com/app/xuanxuan:5.6
```

或者修改 docker-compose.yml 文件，添加持久化目录配置

```bash
services:
  xuanxuan:
  ...
    volumes:
      - /path/to/zentao-persistence:/data
  ...
```
## 五、环境变量

| 变量名           | 默认值        | 说明                             |
| ---------------- | ------------- | -------------------------------- |
| DEBUG            | NULL         | 是否打开调试信息，默认关闭       |
| PHP_SESSION_TYPE | files         | php session 类型，files \| redis |
| PHP_SESSION_PATH | /data/session | php session 存储路径             |



## 六、运行 
### 6.1 单机Docker-compose方式运行

```bash
# 启动服务
make run

# 查看服务状态
make ps

# 查看服务日志
docker-compose logs -f xuanxuan  # 查看zentao日志

```

> **说明：**
>
> - 启动成功后，打开浏览器输入 `http://<你的IP>:8082` 访问管理后台
> - 默认用户名：`admin`，默认密码：`123456`
