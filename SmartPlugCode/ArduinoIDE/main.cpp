#define BLYNK_TEMPLATE_ID "TMPL6shg3fnLg"
#define BLYNK_TEMPLATE_NAME "CrazyPlug"
#define BLYNK_AUTH_TOKEN "h4wUSCXYQ-4z3MfftNKqnm5_T_GFap_B"

#include <Arduino.h>
#include <WiFi.h>
#include <DHT.h>
#include <WiFiClient.h>
#include <BlynkSimpleEsp32.h>
#include <HTTPClient.h>
#include "SinricPro.h"
#include "SinricProSwitch.h"
#include <Adafruit_I2CDevice.h>
#include <SinricProPowerSensor.h> // เพิ่มไลบรารี SinricProPowerSensor

//! circle ip
// String ipAddress = "172.20.10.7"; // IP address
String ipAddress = "172.20.10.7";
String URL = "http://" + ipAddress + "/SmartPlug/sent_data.php";        // URL
String URLUPDATE = "http://" + ipAddress + "/SmartPlug/updateTemp.php"; // URL UPDATE TEMP

char ssid[] = "Saranphat";
char pass[] = "123456789";
// char ssid[] = "Thanawat";
// char pass[] = "00000000";
// char ssid[] = "MESUP_HOUSE_2.4G";
// char pass[] = "Mesup005648";

#define PIN_RED 18   //
#define PIN_GREEN 21 //
#define PIN_BLUE 19  //

// #define SHEET_ID "1mT4Juwo62DCVIIqumCzN01ockN3vmUols5iYcoNU7Yk"                                //! sheet id
// #define SCRIPT_ID "AKfycbym5b7anHNbntApbmCi8hGva8JekxdmLylZbpVeFiiH2s3zJQiAcUKRyHFg7-koVUQnZQ" //! script id

#define APP_KEY "1dd39958-16d2-43e4-a88c-88ad8a4b782d"                                         //! Key Sinric Pro room light
#define APP_SECRET "5e4ff26c-3953-488b-8668-6b8d2cba64c3-016a33b7-fa4d-4c27-8f44-ba7ed2d13a11" //! Key2 Sinric Pro room light

#define APP_KEY_TEMP "1dd39958-16d2-43e4-a88c-88ad8a4b782d"
#define APP_SECRET_TEMP "5e4ff26c-3953-488b-8668-6b8d2cba64c3-016a33b7-fa4d-4c27-8f44-ba7ed2d13a11"

#define DHTPIN 16     // Pin connected to DHT22
#define DHTTYPE DHT22 // Define the type of DHT sensor you are using (DHT11, DHT22, DHT21)
#define RELAY_PIN 26  // Pin connected to Relay

#define device_id_1 "660022a038f6f4a3cdc8bec8" //! device ID 1 light

#define device_id_2 "660c1f4838f6f4a3cdcecd32"

#define DEBOUNCE_TIME 200
void setColor(int red, int green, int blue);
DHT dht(DHTPIN, DHTTYPE);
bool dataSent = false; // Flag to track if data has been sent to Google Sheet
void sentDataTolocalHost(float temperature, float humidity, int elapsedTime)
{

  // Create post data string
  String postData = "temperature=" + String(temperature) + "&humidity=" + String(humidity) + "&runtime=" + String(elapsedTime);
  // Declare HTTPClient object
  HTTPClient http;
  // Check if sending data is successful
  http.begin(URL);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  int httpCode = http.POST(postData);
  String payload = http.getString();
  Serial.print("URL : ");
  Serial.println(URL);
  Serial.print("Data: ");
  Serial.println(postData);
  Serial.print("HTTP Code: ");
  Serial.println(httpCode);
  Serial.print("Payload: ");
  Serial.println(payload);
}
void updateTempRealtime(float temperature, float humidity, int relayState)
{

  // Create post data string
  String postData = "temperature=" + String(temperature) + "&humidity=" + String(humidity) + "&relayState=" + String(relayState);
  // Declare HTTPClient object
  HTTPClient http;
  // Check if sending data is successful
  http.begin(URLUPDATE);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  int httpCode = http.POST(postData);
  String payload = http.getString();

  Serial.print("URL : ");
  Serial.println(URL);
  Serial.print("Data: ");
  Serial.println(postData);
  Serial.print("HTTP Code: ");
  Serial.println(httpCode);
  Serial.print("Payload: ");
  Serial.println(payload);
}
void sendLineMessage(String accessToken, String message)
{
  HTTPClient http;

  // LINE Notify API endpoint
  String url = "https://notify-api.line.me/api/notify";
  // Headers
  http.begin(url);
  http.addHeader("Authorization", "Bearer " + accessToken);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  // Payload
  String payload = "message=" + message;
  // Send POST request
  int httpResponseCode = http.POST(payload);
  // Check for errors
  if (httpResponseCode > 0)
  {
    Serial.print("HTTP Response code: ");
    Serial.println(httpResponseCode);
  }
  else
  {
    Serial.print("Error sending message: ");
    Serial.println(httpResponseCode);
  }
  // Close connection
  http.end();
}
unsigned long La = 0;
unsigned long currentMillis = millis();
unsigned long relayStartTime = 0; // relay เปิด
unsigned long relayEndTime = 0;   // เวลาที่ Relay ถูกปิด
void setupRelays();
void setupFlipSwitches();
void setupSinricPro();
void setupSinricProTemp();
int relayState = LOW;
void setup()
{
  Serial.begin(9600);
  dht.begin();
  pinMode(PIN_RED, OUTPUT);
  pinMode(PIN_GREEN, OUTPUT);
  pinMode(PIN_BLUE, OUTPUT);
  // Connect to Wi-Fi
  digitalWrite(PIN_RED, HIGH);
  digitalWrite(PIN_GREEN, LOW);
  digitalWrite(PIN_BLUE, LOW);
  delay(5000);
  WiFi.begin(ssid, pass);
  Serial.print("Connecting to WiFi " + String(ssid));
  while (WiFi.status() != WL_CONNECTED)
  {
    delay(500);
    Serial.print(".");
  }
  Serial.println("Connected to WiFi");
  digitalWrite(PIN_RED, LOW);
  digitalWrite(PIN_GREEN, LOW);
  digitalWrite(PIN_BLUE, HIGH);

  // Check connection to SinricPro
  static unsigned long lastSinricProCheck = 0;
  const unsigned long sinricProCheckInterval = 5000; // Check every 5 seconds
  if (currentMillis - lastSinricProCheck >= sinricProCheckInterval)
  {
    lastSinricProCheck = currentMillis;
    if (SinricPro.isConnected())
    {
      Serial.println("Connected to SinricPro");
    }
    else
    {
      Serial.println("Not connected to SinricPro");
    }
  }
  setupRelays();
  setupFlipSwitches();
  setupSinricPro();
 setupSinricProTemp();
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, HIGH); // Set Relay to OFF initially
  Blynk.begin(BLYNK_AUTH_TOKEN, ssid, pass);
}
void handleFlipSwitches();
// Function to control relay based on Blynk app input
BLYNK_WRITE(V1)
{
  int relayState = param.asInt(); // Read relay state from Blynk
  if (relayState == 0)
  {
    digitalWrite(RELAY_PIN, HIGH);      // Turn on Relay if the state is 1
    Serial.println("Relay turned OFF"); // Print message when relay is turned off
    // Update SinricPro state
    SinricProSwitch &mySwitch = SinricPro[device_id_1];
    mySwitch.sendPowerStateEvent(false);
  }
  else
  {
    Serial.println("Relay turned ON"); // Print message when relay is turned off
    digitalWrite(RELAY_PIN, LOW);      // Turn off Relay if the state is 0
    // Update SinricPro state
    SinricProSwitch &mySwitch = SinricPro[device_id_1];
    mySwitch.sendPowerStateEvent(true);
  }
}

bool relayOn = false;             // สถานะของ relay
unsigned long lastUpdateTime = 0; // Variable to track last update tim

void loop()
{

  Blynk.run();
  SinricPro.handle();   // Handle Sinric Pro events
  handleFlipSwitches(); // Handle flip switches

  // ส่งค่าอุณหภูมิไปยัง Sinric Pro
  // Send temperature to Sinric Pro
  // Send temperature to Sinric Pro

  float humidity = dht.readHumidity();
  float temperature = dht.readTemperature();

  static float maxHumidity = 0;
  static float maxTemperature = 0;

  if (isnan(humidity) || isnan(temperature))
  {
    Serial.println("Failed to read from DHT sensor!");
  }
  else
  {
    int relayState = digitalRead(RELAY_PIN);
    if (relayState == HIGH)
    { // Relay is OFF
      digitalWrite(PIN_RED, LOW);
      digitalWrite(PIN_GREEN, LOW);
      digitalWrite(PIN_BLUE, HIGH);
      if (relayOn)
      {
        unsigned long currentMillis = millis();
        unsigned long elapsedTime = currentMillis - relayStartTime;
        sentDataTolocalHost(temperature, humidity, elapsedTime / 1000);
        relayOn = false;    // Reset relay status
        maxTemperature = 0; // Reset max temperature
        maxHumidity = 0;    // Reset max humidity
      }
      relayStartTime = 0; // Reset relay start time
                          // Print sensor data and relay status
                          // Serial.print("Relay State: OFF \t");
                          // Serial.print("Humidity: ");
                          // Serial.print(humidity);
                          // Serial.print(" %\t");
                          // Serial.print("Temperature: ");
                          // Serial.println(temperature);
                          // Check if it's time to update the data
      unsigned long currentMillis = millis();
      unsigned long elapsedTime = currentMillis - relayStartTime;
      if (currentMillis - lastUpdateTime >= 5000) // Update every 5 seconds
      {
        updateTempRealtime(temperature, humidity, 0);
        lastUpdateTime = currentMillis; // Update last update time
      }
      if (humidity > 80 || temperature > 80)
      {
        // Send message to LINE Notify
        sendLineMessage("wrYOTdtlZurEfHSs6Lx9OO1kcoJwVFcqR5HXO5c5kIi", "Danger!!: Humidity 🌫 or temperature🌡 is too high! SWITCH OFF NOW!!");
        digitalWrite(PIN_RED, HIGH);   // Turn on red LED
        digitalWrite(RELAY_PIN, HIGH); // Turn off Relay or stop operation

        // Disconnect Wi-Fi
        WiFi.disconnect(true);

        // Enter Deep Sleep mode
        esp_deep_sleep_start();
        // Enter Deep Sleep mode
      }
    }
    else
    { // Relay is ON
      if (!relayOn)

      {
        relayStartTime = millis();
        relayOn = true; // Set relay status to ON
        Serial.println("Relay turned ON");
      }
      digitalWrite(PIN_RED, HIGH);
      digitalWrite(PIN_GREEN, LOW);
      digitalWrite(PIN_BLUE, HIGH);
      // Update max temperature and humidity
      if (temperature > maxTemperature)
      {
        maxTemperature = temperature;
      }
      if (humidity > maxHumidity)
      {
        maxHumidity = humidity;
      }
      unsigned long currentMillis = millis();
      unsigned long elapsedTime = currentMillis - relayStartTime;
      // Print sensor data and relay status
      Serial.print("Relay State: ON \t");
      Serial.print("Humidity: ");
      Serial.print(humidity);
      Serial.print(" %\t");
      Serial.print("Temperature: ");
      Serial.println(temperature);
      Serial.print("Relay has been ON for ");
      Serial.print(elapsedTime / 1000); // Convert milliseconds to seconds
      Serial.println(" seconds");
      // Check if it's time to update the data
      if (currentMillis - lastUpdateTime >= 5000) // Update every 5 seconds
      {
        updateTempRealtime(temperature, humidity, 1);
        lastUpdateTime = currentMillis; // Update last update time
      }
      if (humidity > 80 || temperature > 80)
      {
        // Send message to LINE Notify
        sendLineMessage("wrYOTdtlZurEfHSs6Lx9OO1kcoJwVFcqR5HXO5c5kIi", "Danger!!: Humidity 🌫 or temperature🌡 is too high! SWITCH OFF NOW!!");
        digitalWrite(PIN_RED, HIGH);   // Turn on red LED
        digitalWrite(RELAY_PIN, HIGH); // Turn off Relay or stop operation
                                       // Disconnect Wi-Fi
        WiFi.disconnect(true);
        // Enter Deep Sleep mode
        esp_deep_sleep_start(); //! ป้องกันการ ไฟช็อต
        // Enter Deep Sleep mode
      }
    }
  }
  delay(400);
}
typedef struct
{ // struct for the std::map below
  int relayPIN;
  int flipSwitchPIN;
} deviceConfig_t;

std::map<String, deviceConfig_t> devices = {
    //{deviceId, {relayPIN,  flipSwitchPIN}}
    {device_id_1, {RELAY_PIN}}};
typedef struct
{ // struct for the std::map below
  String deviceId;
  bool lastFlipSwitchState;
  unsigned long lastFlipSwitchChange;
} flipSwitchConfig_t;

std::map<int, flipSwitchConfig_t> flipSwitches; // this map is used to map flipSwitch PINs to deviceId and handling debounce and last flipSwitch state checks
                                                // it will be setup in "setupFlipSwitches" function, using informations from devices map

void setupRelays()
{
  for (auto &device : devices)
  {                                        // for each device (relay, flipSwitch combination)
    int relayPIN = device.second.relayPIN; // get the relay pin
    pinMode(relayPIN, OUTPUT);             // set relay pin to OUTPUT
    digitalWrite(relayPIN, HIGH);
  }
}
void setupFlipSwitches()
{
  for (auto &device : devices)
  {                                      // for each device (relay / flipSwitch combination)
    flipSwitchConfig_t flipSwitchConfig; // create a new flipSwitch configuration

    flipSwitchConfig.deviceId = device.first;    // set the deviceId
    flipSwitchConfig.lastFlipSwitchChange = 0;   // set debounce time
    flipSwitchConfig.lastFlipSwitchState = true; // set lastFlipSwitchState to false (LOW)--

    int flipSwitchPIN = device.second.flipSwitchPIN; // get the flipSwitchPIN

    flipSwitches[flipSwitchPIN] = flipSwitchConfig; // save the flipSwitch config to flipSwitches map
    pinMode(flipSwitchPIN, INPUT_PULLUP);           // set the flipSwitch pin to INPUT
  }
}

bool onPowerState(String deviceId, bool &state)
{
  Serial.printf("%s: %s\r\n", deviceId.c_str(), state ? "on" : "off");
  int relayPIN = devices[deviceId].relayPIN; // get the relay pin for corresponding device
  digitalWrite(relayPIN, !state);
  // Update Blynk button state
  if (state)
  {
    Blynk.virtualWrite(V1, 1); // Set Blynk button state to ON
  }
  else
  {
    Blynk.virtualWrite(V1, 0); // Set Blynk button state to OFF
  }                            // set the new relay state
  return true;
}
void handleFlipSwitches()
{
  unsigned long actualMillis = millis(); // get actual millis
  for (auto &flipSwitch : flipSwitches)
  {                                                                              // for each flipSwitch in flipSwitches map
    unsigned long lastFlipSwitchChange = flipSwitch.second.lastFlipSwitchChange; // get the timestamp when flipSwitch was pressed last time (used to debounce / limit events)

    if (actualMillis - lastFlipSwitchChange > DEBOUNCE_TIME)
    { // if time is > debounce time...

      int flipSwitchPIN = flipSwitch.first;                             // get the flipSwitch pin from configuration
      bool lastFlipSwitchState = flipSwitch.second.lastFlipSwitchState; // get the lastFlipSwitchState
      bool flipSwitchState = digitalRead(flipSwitchPIN);                // read the current flipSwitch state
      if (flipSwitchState != lastFlipSwitchState)
      { // if the flipSwitchState has changed...
#ifdef TACTILE_BUTTON
        if (flipSwitchState)
        { // if the tactile button is pressed
#endif
          flipSwitch.second.lastFlipSwitchChange = actualMillis; // update lastFlipSwitchChange time
          String deviceId = flipSwitch.second.deviceId;          // get the deviceId from config
          int relayPIN = devices[deviceId].relayPIN;             // get the relayPIN from config
          bool newRelayState = !digitalRead(relayPIN);           // set the new relay State
          digitalWrite(relayPIN, newRelayState);                 // set the trelay to the new state

          SinricProSwitch &mySwitch = SinricPro[deviceId]; // get Switch device from SinricPro
          mySwitch.sendPowerStateEvent(!newRelayState);    // send the event

#ifdef TACTILE_BUTTON
        }
#endif
        flipSwitch.second.lastFlipSwitchState = flipSwitchState; // update lastFlipSwitchState
      }
    }
  }
}

void setupSinricPro()
{
  for (auto &device : devices)
  {
    const char *deviceId = device.first.c_str();
    SinricProSwitch &mySwitch = SinricPro[deviceId];
    mySwitch.onPowerState(onPowerState);
  }

  SinricPro.begin(APP_KEY, APP_SECRET);
  SinricPro.restoreDeviceStates(true);
}
void setColor(int red, int green, int blue)
{
  // แปลงค่าสีจาก 0-255 เป็นค่า PWM ที่สามารถใช้กับ analogWrite() ได้ (0-1023)
  int redPWM = map(red, 0, 255, 0, 1023);
  int greenPWM = map(green, 0, 255, 0, 1023);
  int bluePWM = map(blue, 0, 255, 0, 1023);

  // ตั้งค่าสีของ RGB LED โดยใช้ analogWrite()
  analogWrite(PIN_RED, redPWM);
  analogWrite(PIN_GREEN, greenPWM);
  analogWrite(PIN_BLUE, bluePWM);
}
bool onPowerStateTemp(String deviceId, bool &state)
{
  Serial.printf("%s: %s\r\n", deviceId.c_str(), state ? "on" : "off");
  if (state)
  {
    // ใส่โค้ดที่เช็คค่าอุณหภูมิ หรือส่งค่าอุณหภูมิไปยังที่อื่นๆ ที่คุณต้องการทำ
    float temperature = dht.readTemperature(); // อ่านค่าอุณหภูมิจากเซ็นเซอร์ DHT
    // ตรวจสอบว่าการอ่านค่าอุณหภูมิเป็นไปได้หรือไม่
    if (!isnan(temperature))
    {
      // ทำอะไรกับค่าอุณหภูมิที่ได้ โดยสามารถส่งค่าอุณหภูมิไปยังที่ต้องการได้ เช่น Sinric Pro อีกครั้ง
      Serial.print("Temperature: ");
      Serial.println(temperature);
    }
    else
    {
      Serial.println("Failed to read temperature from DHT sensor!");
    }
  }
  // ระบุการจัดการสถานะอื่นๆ หรือคำสั่งที่คุณต้องการให้ Sinric Pro ทำ
  return true;
}

void setupSinricProTemp()
{
  // Add PowerSensor device
  SinricProSwitch &myPowerSensor = SinricPro[device_id_2];
  myPowerSensor.onPowerState(onPowerStateTemp); // Set callback for power state changes

  // Start Sinric Pro
  SinricPro.begin(APP_KEY_TEMP, APP_SECRET_TEMP);
  SinricPro.restoreDeviceStates(true);
}
