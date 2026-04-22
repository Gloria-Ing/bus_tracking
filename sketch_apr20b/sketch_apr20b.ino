#include <WiFi.h>
#include <HTTPClient.h>
#include <TinyGPS++.h>
#include <HardwareSerial.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

// ==================== PIN CONFIGURATION ====================
// RFID Pins
#define RFID_SS_PIN   5
#define RFID_RST_PIN  4
#define RFID_SCK_PIN  18
#define RFID_MOSI_PIN 23
#define RFID_MISO_PIN 19

// GPS Pins
#define GPS_RX_PIN    16
#define GPS_TX_PIN    17

// OLED Pins (I2C)
#define OLED_SDA      21
#define OLED_SCL      22

// LED and Buzzer
#define LED_PIN       2
#define BUZZER_PIN    2

// OLED Configuration
#define SCREEN_WIDTH  128
#define SCREEN_HEIGHT 64
#define OLED_ADDRESS  0x3C

// WiFi Credentials
const char* ssid = "AMOTECH ELECTRONICS Ltd";
const char* password = "AMOTECH@123";
const char* serverHost = "192.168.1.151";      // Change to your PC's IP
String serverUrl = "http://" + String(serverHost) + "/bus_tracking/api.php";
String busNumber = "BUS101";

// ==================== OBJECTS ====================
TinyGPSPlus gps;
HardwareSerial gpsSerial(2);
MFRC522 rfid(RFID_SS_PIN, RFID_RST_PIN);
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

// ==================== VARIABLES ====================
float currentLat = 0, currentLng = 0, currentSpeed = 0;
int satelliteCount = 0;
bool gpsFixed = false;
bool wifiConnected = false;
unsigned long lastGPSUpdate = 0;
unsigned long lastSeatUpdate = 0;
unsigned long lastHeartbeat = 0;
unsigned long lastOLEDUpdate = 0;
String lastCardUID = "";
int availableSeats = 0;
int totalSeats = 40;
int displayPage = 0;
unsigned long lastPageSwitch = 0;
String lastMessage = "";
unsigned long messageEndTime = 0;

// ==================== SETUP ====================
void setup() {
  Serial.begin(115200);
Serial.println("\n╔════════════════════════════════════╗");
  Serial.println("║   ESP32 Bus Tracking System v4.0   ║");
  Serial.println("║        with OLED Display           ║");
  Serial.println("╚════════════════════════════════════╝\n");
  
  // Initialize OLED
  initOLED();
  
  // Initialize SPI for RFID
  SPI.begin(RFID_SCK_PIN, RFID_MISO_PIN, RFID_MOSI_PIN, RFID_SS_PIN);
  
  // Initialize RFID
  rfid.PCD_Init();
  Serial.println("✅ RFID initialized");
  displayMessage("RFID", "Initialized");
  
  // Initialize GPS
  gpsSerial.begin(9600, SERIAL_8N1, GPS_RX_PIN, GPS_TX_PIN);
  Serial.println("✅ GPS initialized");
  displayMessage("GPS", "Initializing...");
  
  // Initialize pins
  pinMode(LED_PIN, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW);
  
  // Connect to WiFi
  connectToWiFi();
  
  // Get initial seat data
  fetchSeatAvailability();
  
  // Startup indication
  startupAnimation();
  for(int i=0; i<2; i++) {
    digitalWrite(LED_PIN, HIGH);
    tone(BUZZER_PIN, 1000, 100);
    delay(200);
    digitalWrite(LED_PIN, LOW);
    delay(100);
  }
  
  Serial.println("\n✅ System Ready!");
  displayMessage("System Ready", "Tap RFID Card");
  delay(2000);
}

// ==================== INITIALIZATION FUNCTIONS ====================
void initOLED() {
  Wire.begin(OLED_SDA, OLED_SCL);
  
  if(!display.begin(SSD1306_SWITCHCAPVCC, OLED_ADDRESS)) {
    Serial.println("❌ OLED not found!");
    Serial.println("   Check wiring: SDA=GPIO21, SCL=GPIO22");
  } else {
    Serial.println("✅ OLED initialized");
    display.clearDisplay();
    display.setTextSize(1);
    display.setTextColor(SSD1306_WHITE);
    display.setCursor(0, 0);
    display.println("ESP32 Bus System");
    display.println("Loading...");
    display.display();
  }
}

void connectToWiFi() {
  displayMessage("WiFi", "Connecting...");
  Serial.print("📡 Connecting to WiFi");
  
  WiFi.begin(ssid, password);
  int attempts = 0;
  
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500);
    Serial.print(".");
    attempts++;
    
    // Update OLED with dots
    if (attempts % 4 == 0) {
      String dots = String('.', (attempts / 4) % 4);
      displayMessage("WiFi", ssid + dots);
    }
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✅ WiFi connected!");
    Serial.print("📱 IP: ");
    Serial.println(WiFi.localIP());
    wifiConnected = true;
    displayMessage("WiFi OK", WiFi.localIP().toString());
    delay(1500);
  } else {
    Serial.println("\n❌ WiFi failed!");
    wifiConnected = false;
    displayMessage("WiFi Error", "Check credentials");
    delay(2000);
  }
}

// ==================== MAIN LOOP ====================
void loop() {
  // Read GPS data
  readGPSData();
  
  // Send location every 5 seconds
  if (gpsFixed && (millis() - lastGPSUpdate >= 5000)) {
    sendLocationToServer();
    lastGPSUpdate = millis();
  }
  
  // Check for RFID card
  checkRFIDCard();
  
  // Update seat info every 10 seconds
  if (millis() - lastSeatUpdate >= 10000) {
    fetchSeatAvailability();
    lastSeatUpdate = millis();
  }
  
  // Send heartbeat every 30 seconds
  if (millis() - lastHeartbeat >= 30000) {
    sendHeartbeat();
    lastHeartbeat = millis();
  }
  
  // Update OLED display
  if (millis() - lastOLEDUpdate >= 1000) {
    updateOLED();
    lastOLEDUpdate = millis();
  }
  
  // Change display page every 5 seconds
  if (millis() - lastPageSwitch >= 5000 && messageEndTime == 0) {
    displayPage = (displayPage + 1) % 4;
    lastPageSwitch = millis();
  }
  
  delay(50);
}

// ==================== GPS FUNCTIONS ====================
void readGPSData() {
  while (gpsSerial.available() > 0) {
    char c = gpsSerial.read();
    if (gps.encode(c)) {
      if (gps.location.isValid()) {
        currentLat = gps.location.lat();
        currentLng = gps.location.lng();
        currentSpeed = gps.speed.kmph();
        satelliteCount = gps.satellites.value();
        
        if (!gpsFixed) {
          gpsFixed = true;
          Serial.println("\n✅ GPS Fix acquired!");
          Serial.print("📍 Location: ");
          Serial.print(currentLat, 6);
          Serial.print(", ");
          Serial.println(currentLng, 6);
          displayMessage("GPS Fixed", "Location ready");
          tone(BUZZER_PIN, 2000, 200);
        }
      } else {
        if (gpsFixed) {
          gpsFixed = false;
          Serial.println("\n⚠️ GPS signal lost");
          displayMessage("GPS Lost", "Searching...");
        }
      }
    }
  }
}

void sendLocationToServer() {
  if (WiFi.status() != WL_CONNECTED) return;
  
  HTTPClient http;
  String url = serverUrl + "?action=update_location" +
               "&bus=" + busNumber +
               "&lat=" + String(currentLat, 6) +
               "&lng=" + String(currentLng, 6) +
               "&speed=" + String(currentSpeed, 1);
  
  http.begin(url);
  http.setTimeout(3000);
  
  int httpCode = http.GET();
  if (httpCode > 0) {
    String response = http.getString();
    if (response == "OK") {
      Serial.print("📍");
    }
  }
  http.end();
}

// ==================== RFID FUNCTIONS ====================
void checkRFIDCard() {
  if (!rfid.PICC_IsNewCardPresent()) return;
  if (!rfid.PICC_ReadCardSerial()) return;
  
  String uid = getCardUID();
  if (uid != lastCardUID) {
    lastCardUID = uid;
    processRFIDCard(uid);
    delay(1500);
  }
  rfid.PICC_HaltA();
}

String getCardUID() {
  String uid = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    if (rfid.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(rfid.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();
  return uid;
}

void processRFIDCard(String uid) {
  Serial.print("\n🔖 Card: ");
  Serial.println(uid);
  
  // Visual feedback
  digitalWrite(LED_PIN, HIGH);
  displayMessage("Processing", "Card: " + uid.substring(0, 8));
  
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("❌ WiFi not connected");
    displayMessage("WiFi Error", "Cannot process");
    beepError();
    digitalWrite(LED_PIN, LOW);
    return;
  }
  
  HTTPClient http;
  String url = serverUrl + "?action=process_rfid&uid=" + uid + "&bus=" + busNumber;
  http.begin(url);
  http.setTimeout(5000);
  
  int httpCode = http.GET();
  if (httpCode > 0) {
    String response = http.getString();
    response.trim();
    Serial.println("Response: " + response);
    
    if (response == "ENTRY_SUCCESS") {
      Serial.println("✅ ENTRY GRANTED");
      displayMessage("✓ ENTRY", "Welcome aboard!");
      beepSuccess();
      fetchSeatAvailability();
      animateSuccess();
      
    } else if (response == "EXIT_SUCCESS") {
      Serial.println("✅ EXIT GRANTED");
      displayMessage("✓ EXIT", "Thank you!");
      beepSuccess();
      fetchSeatAvailability();
      animateSuccess();
      
    } else if (response == "NO_SEATS_AVAILABLE") {
      Serial.println("❌ BUS FULL");
      displayMessage("✗ BUS FULL", "No seats available");
      beepError();
      animateError();
      
    } else {
      Serial.println("❌ ERROR: " + response);
      displayMessage("✗ ERROR", response);
      beepError();
      animateError();
    }
    
  } else {
    Serial.print("❌ HTTP Error: ");
    Serial.println(httpCode);
    displayMessage("HTTP Error", String(httpCode));
    beepError();
  }
  
  http.end();
  delay(2000);
  digitalWrite(LED_PIN, LOW);
}

// ==================== SEAT FUNCTIONS ====================
void fetchSeatAvailability() {
  if (WiFi.status() != WL_CONNECTED) return;
  
  HTTPClient http;
  String url = serverUrl + "?action=get_seats&bus=" + busNumber;
  http.begin(url);
  http.setTimeout(3000);
  
  int code = http.GET();
  if (code > 0) {
    String seats = http.getString();
    seats.trim();
    availableSeats = seats.toInt();
    Serial.print("💺 Seats: ");
    Serial.println(availableSeats);
  }
  http.end();
}

void sendHeartbeat() {
  if (WiFi.status() != WL_CONNECTED) return;
  
  HTTPClient http;
  String url = serverUrl + "?action=heartbeat&bus=" + busNumber + "&status=online";
  http.begin(url);
  http.setTimeout(2000);
  http.GET();
  http.end();
  Serial.print("💓");
}

// ==================== OLED DISPLAY FUNCTIONS ====================
void updateOLED() {
  // Check if we're showing a temporary message
  if (messageEndTime > 0 && millis() < messageEndTime) {
    return;  // Keep showing temporary message
  } else {
    messageEndTime = 0;  // Clear message flag
  }
  
  display.clearDisplay();
  
  // Draw border
  display.drawRect(0, 0, SCREEN_WIDTH, SCREEN_HEIGHT, SSD1306_WHITE);
  
  // Header with bus number and status
  display.setTextSize(1);
  display.setCursor(2, 2);
  display.print("🚌 ");
  display.print(busNumber);
  
  // WiFi icon
  display.setCursor(65, 2);
  if (wifiConnected) {
    display.print("📶");
  } else {
    display.print("📡!");
  }
  
  // GPS status
  display.setCursor(85, 2);
  if (gpsFixed) {
    display.print("GPS✓");
  } else {
    display.print("GPS⌛");
  }
  
  // Separator line
  display.drawLine(0, 12, SCREEN_WIDTH, 12, SSD1306_WHITE);
  
  // Main content based on page
  switch(displayPage) {
    case 0:
      displaySeatInfo();
      break;
    case 1:
      displayGPSInfo();
      break;
    case 2:
      displaySystemInfo();
      break;
    case 3:
      displayCardInfo();
      break;
  }
  
  // Page indicator dots
  displayPageIndicator();
  
  display.display();
}

void displaySeatInfo() {
  display.setTextSize(2);
  display.setCursor(10, 20);
  display.print("SEATS");
  
  display.setTextSize(3);
  display.setCursor(30, 38);
  display.print(availableSeats);
  
  display.setTextSize(1);
  display.setCursor(85, 48);
  display.print("/");
  display.print(totalSeats);
  
  // Progress bar
  int barWidth = map(availableSeats, 0, totalSeats, 0, 100);
  if (barWidth < 0) barWidth = 0;
  if (barWidth > 100) barWidth = 100;
  
  display.drawRect(10, 58, 100, 4, SSD1306_WHITE);
  
  // Color based on availability
  if (availableSeats < 10) {
    display.fillRect(10, 58, barWidth, 4, SSD1306_WHITE);
  } else if (availableSeats < 20) {
    display.fillRect(10, 58, barWidth, 4, SSD1306_WHITE);
  } else {
    display.fillRect(10, 58, barWidth, 4, SSD1306_WHITE);
  }
}

void displayGPSInfo() {
  display.setTextSize(1);
  display.setCursor(5, 18);
  
  if (gpsFixed) {
    display.print("Lat: ");
    display.println(String(currentLat, 6));
    display.setCursor(5, 30);
    display.print("Lng: ");
    display.println(String(currentLng, 6));
    display.setCursor(5, 42);
    display.print("Speed: ");
    display.print(currentSpeed, 1);
    display.print(" km/h");
    display.setCursor(5, 54);
    display.print("Satellites: ");
    display.print(satelliteCount);
  } else {
    display.println("Waiting for GPS...");
    display.setCursor(5, 30);
    display.print("Satellites: ");
    display.print(satelliteCount);
    display.setCursor(5, 42);
    display.print("Signal: ");
    if (satelliteCount > 0) {
      display.print("Weak");
    } else {
      display.print("None");
    }
  }
}

void displaySystemInfo() {
  display.setTextSize(1);
  display.setCursor(5, 18);
  display.print("WiFi: ");
  display.println(wifiConnected ? "Connected" : "Disconnected");
  
  display.setCursor(5, 30);
  display.print("RSSI: ");
  display.print(WiFi.RSSI());
  display.println(" dBm");
  
  display.setCursor(5, 42);
  display.print("Free Heap: ");
  display.print(ESP.getFreeHeap() / 1024);
  display.println(" KB");
  
  display.setCursor(5, 54);
  display.print("Uptime: ");
  display.print(millis() / 1000 / 60);
  display.println(" min");
}

void displayCardInfo() {
  display.setTextSize(1);
  display.setCursor(5, 18);
  display.println("Last Card:");
  
  if (lastCardUID.length() > 0) {
    display.setTextSize(2);
    display.setCursor(15, 32);
    display.println(lastCardUID);
    display.setTextSize(1);
    display.setCursor(5, 54);
    display.println("Tap card to scan new");
  } else {
    display.setTextSize(1);
    display.setCursor(20, 35);
    display.println("No card scanned");
    display.setCursor(15, 50);
    display.println("Tap RFID card");
  }
}

void displayPageIndicator() {
  int dotX = SCREEN_WIDTH - (4 * 6);
  int dotY = SCREEN_HEIGHT - 5;
  
  for (int i = 0; i < 4; i++) {
    if (i == displayPage) {
      display.fillCircle(dotX + (i * 6), dotY, 2, SSD1306_WHITE);
    } else {
      display.drawCircle(dotX + (i * 6), dotY, 2, SSD1306_WHITE);
    }
  }
}

void displayMessage(String line1, String line2) {
  display.clearDisplay();
  display.setTextSize(1);
  display.setCursor(0, 20);
  display.println(line1);
  display.setTextSize(1);
  display.setCursor(0, 35);
  display.println(line2);
  display.display();
  
  messageEndTime = millis() + 2500;  // Show for 2.5 seconds
}

// ==================== ANIMATION FUNCTIONS ====================
void startupAnimation() {
  for (int i = 0; i <= SCREEN_WIDTH; i += 10) {
    display.clearDisplay();
    display.drawRect(0, 0, i, SCREEN_HEIGHT, SSD1306_WHITE);
    display.setCursor(30, 28);
    display.print("Starting...");
    display.display();
    delay(30);
  }
  delay(300);
}

void animateSuccess() {
  for (int i = 0; i < 2; i++) {
    display.clearDisplay();
    display.setTextSize(3);
    display.setCursor(45, 20);
    display.print("✓");
    display.display();
    delay(150);
    display.clearDisplay();
    delay(100);
  }
}

void animateError() {
  for (int i = 0; i < 3; i++) {
    display.clearDisplay();
    display.setTextSize(3);
    display.setCursor(45, 20);
    display.print("✗");
    display.display();
    delay(100);
    display.clearDisplay();
    delay(50);
  }
}

// ==================== INDICATOR FUNCTIONS ====================
void beepSuccess() {
  tone(BUZZER_PIN, 1500, 150);
  delay(200);
  tone(BUZZER_PIN, 2000, 150);
}

void beepError() {
  for(int i=0; i<3; i++) {
    tone(BUZZER_PIN, 500, 150);
    delay(200);
  }
}

// ==================== UTILITY FUNCTIONS ====================
void printSystemInfo() {
Serial.println("\n╔════════════════════════════════╗");
  Serial.println("║     SYSTEM INFORMATION         ║");
  Serial.println("╚════════════════════════════════╝");
  Serial.print("Bus: ");
  Serial.println(busNumber);
  Serial.print("WiFi: ");
  Serial.println(wifiConnected ? "Connected" : "Disconnected");
  Serial.print("IP: ");
  Serial.println(WiFi.localIP());
  Serial.print("GPS: ");
  Serial.println(gpsFixed ? "Fixed" : "No Fix");
  Serial.print("Seats: ");
  Serial.println(availableSeats);
  Serial.print("Heap: ");
  Serial.print(ESP.getFreeHeap() / 1024);
  Serial.println(" KB");
  Serial.println("══════════════════════════════════\n");
}