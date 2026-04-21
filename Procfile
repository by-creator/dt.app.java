web: java -Dspring.profiles.active=heroku -Dserver.port=$PORT -Xmx350m -XX:+UseG1GC $JAVA_OPTS -jar target/dt-app-1.0.0.jar
web: mvn spring-boot:run
release: pip3 install -r automatisation/requirements.txt
