// ========================= ESP32 FINAL DHT22 =========================

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

#include <DHT.h>
#include <LiquidCrystal_I2C.h>
#include <Wire.h>

// ================= WIFI =================
const char* ssid = "Lab TI lantai 1";
const char* password = "";

// ================= API =================
String serverName = "https://10.10.3.86/smart_cage/.php";

// ================= LCD =================
LiquidCrystal_I2C lcd(0x27, 16, 2);

// ================= DHT22 =================
#define DHTPIN 25
#define DHTTYPE DHT22

DHT dht(DHTPIN, DHTTYPE);

// ================= RELAY =================
#define FAN_RELAY 33
#define LAMP_RELAY 32

// ================= LDR =================
#define LDR_PIN 34

// ================= THRESHOLD =================
const float TEMP_THRESHOLD = 35.0;

// GELAP = NILAI BESAR
const int LDR_THRESHOLD = 2000;

// ================= TIMER DATABASE =================
unsigned long lastSend = 0;

void setup() {

  Serial.begin(115200);

  // ================= RELAY =================
  pinMode(FAN_RELAY, OUTPUT);
  pinMode(LAMP_RELAY, OUTPUT);

  // ACTIVE LOW RELAY
  digitalWrite(FAN_RELAY, HIGH);
  digitalWrite(LAMP_RELAY, HIGH);

  // ================= LCD =================
  Wire.begin(26, 27);

  lcd.init();
  lcd.backlight();

  lcd.setCursor(0,0);
  lcd.print("Connecting WiFi");

  // ================= DHT22 =================
  dht.begin();

  // ================= WIFI =================
  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {

    delay(500);
    Serial.print(".");
  }

  Serial.println("");
  Serial.println("WiFi Connected");

  lcd.clear();
  lcd.setCursor(0,0);
  lcd.print("WiFi Connected");

  delay(2000);
  lcd.clear();
}

void loop() {

  // ================= SENSOR =================

  float humidity = dht.readHumidity();

  float temperature = dht.readTemperature();

  int ldrValue = analogRead(LDR_PIN);

  // ================= ERROR SENSOR =================

  if (isnan(humidity) || isnan(temperature)) {

    Serial.println("DHT22 ERROR");

    lcd.clear();

    lcd.setCursor(0,0);
    lcd.print("DHT22 ERROR");

    delay(1000);

    return;
  }

  // ================= FAN =================

  String fanStatus;

  if (temperature >= TEMP_THRESHOLD) {

    digitalWrite(FAN_RELAY, LOW);

    fanStatus = "ON";

  } else {

    digitalWrite(FAN_RELAY, HIGH);

    fanStatus = "OFF";
  }

  // ================= LAMP =================

  // GELAP = NILAI BESAR
  if (ldrValue > LDR_THRESHOLD) {

    digitalWrite(LAMP_RELAY, LOW);

  } else {

    digitalWrite(LAMP_RELAY, HIGH);
  }

  // ================= STATUS RELAY =================

  String lampStatus;

  if (digitalRead(LAMP_RELAY) == LOW) {

    // ACTIVE LOW
    lampStatus = "ON";

  } else {

    lampStatus = "OFF";
  }

  // ================= LCD =================

  lcd.clear();

  lcd.setCursor(0,0);

  lcd.print("T:");
  lcd.print(temperature,1);

  lcd.print((char)223);
  lcd.print("C");

  lcd.setCursor(10,0);
  lcd.print(fanStatus);

  lcd.setCursor(0,1);

  lcd.print("L:");
  lcd.print(lampStatus);

  lcd.setCursor(8,1);

  lcd.print("H:");
  lcd.print(humidity,0);
  lcd.print("%");

  // ================= SERIAL =================

  Serial.println("========== SENSOR DATA ==========");

Serial.print("Temperature : ");
Serial.print(temperature);
Serial.println(" °C");

Serial.print("Humidity    : ");
Serial.print(humidity);
Serial.println(" %");

Serial.print("LDR Value   : ");
Serial.println(ldrValue);

Serial.print("Fan Status  : ");
Serial.println(fanStatus);

Serial.print("Lamp Status : ");
Serial.println(lampStatus);

Serial.println("=================================");
Serial.println();

  // ================= KIRIM DATABASE PER 1 MENIT =================

  if (millis() - lastSend >= 60000) {

    lastSend = millis();

    Serial.println("Sending data to database...");

    if (WiFi.status() == WL_CONNECTED) {

      HTTPClient http;

      http.begin(serverName);

      http.addHeader("Content-Type", "application/json");

      StaticJsonDocument<256> doc;

      doc["temperature"] = temperature;
      doc["humidity"] = humidity;
      doc["fan_status"] = fanStatus;
      doc["lamp_status"] = lampStatus;

      String jsonString;

      serializeJson(doc, jsonString);

      Serial.println(jsonString);

      int httpResponseCode =
      http.POST(jsonString);

      Serial.print("HTTP Response: ");
      Serial.println(httpResponseCode);

      String response =
      http.getString();

      Serial.println(response);

      http.end();

    } else {

      Serial.println("WiFi Disconnected");
    }
  }

  // ================= REALTIME LOOP =================
  delay(500);
}