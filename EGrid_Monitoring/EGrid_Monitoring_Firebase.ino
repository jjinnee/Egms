#include <WiFiNINA.h>
#include <avr/wdt.h>

// ==================== WiFi Configuration ====================
const char* ssid = "Bacalla";
const char* password = "0101010101";

// ==================== Firebase Configuration ====================
// Replace with your Firebase project details
#define FIREBASE_HOST "energy-grid-monitoring-system.firebase.com"  // Without https:// 
#define FIREBASE_AUTH "pcq8fFfezyiMpkFRv5I7zzOrs645b2399y3RJLBV"    // Database Secret or API Key
#define FIREBASE_PATH "/devices"                         // Path in database

// ==================== Device Configuration ====================
const char* deviceId = "01";
const bool TEST_MODE_FLAG = true;
const int sensorPin = 2;

// ==================== Timing ====================
unsigned long lastHeartbeat = 0;
const unsigned long heartbeatInterval = 5000;  // 5 seconds

// ==================== SSL Client ====================
WiFiSSLClient client;

// ==================== WiFi Functions ====================
bool ensureWiFi()
{
    if (WiFi.status() == WL_CONNECTED) return true;

    Serial.println("Reconnecting WiFi...");
    WiFi.disconnect();
    WiFi.begin(ssid, password);

    unsigned long start = millis();
    while (WiFi.status() != WL_CONNECTED && millis() - start < 15000UL) {
        delay(500);
        Serial.print(".");
    }
    Serial.println();

    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("WiFi reconnect failed.");
        return false;
    }

    Serial.print("WiFi Connected! IP: ");
    Serial.println(WiFi.localIP());
    return true;
}

// ==================== Firebase Functions ====================
void sendToFirebase(const String& status)
{
    if (!ensureWiFi()) return;

    Serial.println("Connecting to Firebase...");
    
    // Connect to Firebase using HTTPS (port 443)
    if (!client.connect(FIREBASE_HOST, 443)) {
        Serial.println("Firebase connection failed!");
        return;
    }

    // Get current timestamp (milliseconds since boot - for ordering)
    unsigned long timestamp = millis();

    // Build JSON payload
    String payload = "{";
    payload += "\"device_id\":\"" + String(deviceId) + "\",";
    payload += "\"status\":\"" + status + "\",";
    payload += "\"timestamp\":" + String(timestamp) + ",";
    payload += "\"last_seen\":{\".sv\":\"timestamp\"}";  // Server timestamp
    payload += "}";

    // Build the path: /devices/01.json?auth=YOUR_AUTH
    String path = String(FIREBASE_PATH) + "/" + String(deviceId) + ".json?auth=" + FIREBASE_AUTH;

    // Build HTTP PUT request (PUT to update/create specific node)
    String request = "PUT " + path + " HTTP/1.1\r\n";
    request += "Host: " + String(FIREBASE_HOST) + "\r\n";
    request += "Content-Type: application/json\r\n";
    request += "Connection: close\r\n";
    request += "Content-Length: " + String(payload.length()) + "\r\n";
    request += "\r\n";
    request += payload;

    // Send request
    client.print(request);
    
    Serial.println("--- Firebase Request ---");
    Serial.println("PUT " + path);
    Serial.println(payload);

    // Wait for response
    unsigned long timeout = millis();
    while (client.available() == 0) {
        if (millis() - timeout > 10000) {
            Serial.println(">>> Firebase timeout");
            client.stop();
            return;
        }
    }

    // Read response
    Serial.println("--- Firebase Response ---");
    while (client.available()) {
        String line = client.readStringUntil('\n');
        Serial.println(line);
    }
    Serial.println("-------------------------");

    client.stop();
    delay(50);
}

// Alternative: Push data to create new entry each time (for logging)
void pushToFirebase(const String& status)
{
    if (!ensureWiFi()) return;

    Serial.println("Pushing to Firebase...");
    
    if (!client.connect(FIREBASE_HOST, 443)) {
        Serial.println("Firebase connection failed!");
        return;
    }

    // Build JSON payload
    String payload = "{";
    payload += "\"device_id\":\"" + String(deviceId) + "\",";
    payload += "\"status\":\"" + status + "\",";
    payload += "\"timestamp\":{\".sv\":\"timestamp\"}";  // Server timestamp
    payload += "}";

    // Build the path for logging: /logs.json (POST creates new entry with unique key)
    String path = "/logs.json?auth=" + String(FIREBASE_AUTH);

    // Build HTTP POST request
    String request = "POST " + path + " HTTP/1.1\r\n";
    request += "Host: " + String(FIREBASE_HOST) + "\r\n";
    request += "Content-Type: application/json\r\n";
    request += "Connection: close\r\n";
    request += "Content-Length: " + String(payload.length()) + "\r\n";
    request += "\r\n";
    request += payload;

    client.print(request);

    unsigned long timeout = millis();
    while (client.available() == 0) {
        if (millis() - timeout > 10000) {
            Serial.println(">>> Firebase timeout");
            client.stop();
            return;
        }
    }

    while (client.available()) {
        Serial.print((char)client.read());
    }
    Serial.println();

    client.stop();
}

// ==================== Setup ====================
void setup()
{
    Serial.begin(9600);
    pinMode(sensorPin, INPUT);
    delay(500);

    Serial.println("=================================");
    Serial.println("  E-Grid Monitoring (Firebase)  ");
    Serial.println("=================================");
    Serial.print("Device ID: ");
    Serial.println(deviceId);
    Serial.print("Firebase Host: ");
    Serial.println(FIREBASE_HOST);
    Serial.println();

    Serial.println("Connecting to WiFi...");
    WiFi.begin(ssid, password);

    if (!ensureWiFi()) {
        Serial.println("WiFi failed, restarting with watchdog.");
        wdt_enable(WDTO_1S);
        while (true) {}
    }
}

// ==================== Main Loop ====================
void loop()
{
    String status;
    
    if (TEST_MODE_FLAG) {
        status = "ON";
    } else {
        status = digitalRead(sensorPin) == HIGH ? "ON" : "OFF";
    }

    if (millis() - lastHeartbeat >= heartbeatInterval) {
        Serial.print("Heartbeat -> ");
        Serial.println(status);
        
        // Send to Firebase (updates device status)
        sendToFirebase(status);
        
        // Optional: Also log each reading
        // pushToFirebase(status);
        
        lastHeartbeat = millis();
    }
}

