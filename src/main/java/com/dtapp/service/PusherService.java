package com.dtapp.service;

import com.dtapp.config.PusherProperties;
import com.pusher.rest.Pusher;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Service;

@Service
public class PusherService {

    private static final Logger log = LoggerFactory.getLogger(PusherService.class);
    private static final String CHANNEL = "gfa-display";
    private static final String EVENT = "display-update";

    private final Pusher pusher;
    private final String pusherKey;
    private final String pusherCluster;

    public PusherService(PusherProperties props) {
        this.pusherKey = props.getKey();
        this.pusherCluster = props.getCluster();
        this.pusher = new Pusher(props.getAppId(), props.getKey(), props.getSecret());
        this.pusher.setCluster(props.getCluster());
        this.pusher.setEncrypted(true);
    }

    public void triggerDisplayUpdate(Object data) {
        try {
            pusher.trigger(CHANNEL, EVENT, data);
        } catch (Exception e) {
            log.warn("Pusher trigger failed: {}", e.getMessage());
        }
    }

    public String getKey() {
        return pusherKey;
    }

    public String getCluster() {
        return pusherCluster;
    }
}
