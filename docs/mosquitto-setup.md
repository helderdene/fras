# Mosquitto MQTT Broker Setup

This guide covers installing and configuring the Mosquitto MQTT broker that FRAS connects to for camera communication.

## Installation

### Ubuntu / Debian

```bash
sudo apt update
sudo apt install mosquitto mosquitto-clients
sudo systemctl enable mosquitto
sudo systemctl start mosquitto
```

### macOS (Homebrew)

```bash
brew install mosquitto
brew services start mosquitto
```

## Configuration

Edit the Mosquitto configuration file:

- **Ubuntu/Debian:** `/etc/mosquitto/conf.d/hds-fras.conf`
- **macOS:** `/opt/homebrew/etc/mosquitto/mosquitto.conf`

Add the following:

```conf
listener 1883
allow_anonymous false
password_file /etc/mosquitto/passwd
```

## Creating a User

Create a password file and add the FRAS user:

```bash
sudo mosquitto_passwd -c /etc/mosquitto/passwd hds-fras
```

To add additional users without overwriting the file:

```bash
sudo mosquitto_passwd /etc/mosquitto/passwd another-user
```

Restart Mosquitto after changes:

```bash
sudo systemctl restart mosquitto
```

## Testing the Connection

Open two terminals to verify the broker works.

**Terminal 1 -- Subscribe:**

```bash
mosquitto_sub -t 'mqtt/face/#' -u hds-fras -P <password> -v
```

**Terminal 2 -- Publish:**

```bash
mosquitto_pub -t 'mqtt/face/heartbeat' -m '{"Time":"2024-01-01T00:00:00"}' -u hds-fras -P <password>
```

You should see the message appear in Terminal 1.

## Laravel Environment Variables

Update your `.env` file with the broker connection details:

```env
MQTT_HOST=127.0.0.1
MQTT_PORT=1883
MQTT_USERNAME=hds-fras
MQTT_PASSWORD=your-password-here
```

These variables are read by both `config/hds.php` and `config/mqtt-client.php`.

## Network Requirements

- Cameras must be able to reach the Mosquitto broker on port **1883** (TCP).
- The Laravel server must also reach the broker on the same port.
- If the broker runs on the same server as Laravel, `MQTT_HOST=127.0.0.1` is correct.
- For remote brokers, ensure firewall rules allow TCP port 1883 from camera and server subnets.
- MQTT TLS (port 8883) is not used in v1 -- the broker runs on an internal trusted network.
