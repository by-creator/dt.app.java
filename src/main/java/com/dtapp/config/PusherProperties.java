package com.dtapp.config;

import org.springframework.boot.context.properties.ConfigurationProperties;
import org.springframework.stereotype.Component;

@Component
@ConfigurationProperties(prefix = "pusher")
public class PusherProperties {

    private String appId;
    private String key;
    private String secret;
    private String cluster;
    private String scheme = "https";

    public String getAppId() { return appId; }
    public void setAppId(String appId) { this.appId = appId; }

    public String getKey() { return key; }
    public void setKey(String key) { this.key = key; }

    public String getSecret() { return secret; }
    public void setSecret(String secret) { this.secret = secret; }

    public String getCluster() { return cluster; }
    public void setCluster(String cluster) { this.cluster = cluster; }

    public String getScheme() { return scheme; }
    public void setScheme(String scheme) { this.scheme = scheme; }
}
