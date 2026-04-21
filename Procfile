web: java -Dspring.profiles.active=heroku -Dserver.port=$PORT -Xmx200m -Xss512k -XX:+UseSerialGC -XX:MaxMetaspaceSize=100m $JAVA_OPTS -jar target/dt-app-1.0.0.jar
release: pip install -r requirements.txt
