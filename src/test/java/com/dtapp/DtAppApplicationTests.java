package com.dtapp;

import org.junit.jupiter.api.Test;
import org.springframework.boot.test.context.SpringBootTest;

@SpringBootTest(properties = "app.data-initializer.enabled=false")
public class DtAppApplicationTests {

    @Test
    void contextLoads() {
        // Test that the application context loads successfully
    }
}
