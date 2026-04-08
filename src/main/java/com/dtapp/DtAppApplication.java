package com.dtapp;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.scheduling.annotation.EnableAsync;

@SpringBootApplication
@EnableAsync
public class DtAppApplication {

    public static void main(String[] args) {
        SpringApplication.run(DtAppApplication.class, args);
    }
}
