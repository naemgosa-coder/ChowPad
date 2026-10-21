# ChowPad: ระบบจองห้องประชุมศาลากลางจังหวัด

ระบบจองห้องประชุมแบบเรียลไทม์ แจ้งซ่อมบำรุง และลงเวลา/ลางาน เขียนด้วย PHP ล้วน (ไม่ใช้ framework) ตามมาตรฐาน PSR-4 และ PSR-12
ใช้ MySQL ผ่าน PDO แบบ prepared statement ทุกคำสั่ง และหน้าตาตาม [DESIGN_SYSTEM.md](DESIGN_SYSTEM.md)

## สิ่งที่ต้องมี

| โปรแกรม | เวอร์ชัน | หมายเหตุ |
|---|---|---|
| PHP | 8.1 ขึ้นไป | ต้องเปิด extension `pdo_mysql`, `mbstring`, `fileinfo`, `gd` |
| MySQL | 8.0 ขึ้นไป (หรือ MariaDB 10.6+) | |
| Composer | 2.x | ใช้สร้าง autoload และติดตั้งเครื่องมือตรวจโค้ด |

**Laragon (Windows)** มีครบทุกอย่างแล้ว ให้เปิด Laragon แล้วกด **Start All** เพื่อเปิด MySQL
ถ้าพิมพ์ `php` ใน terminal แล้วไม่พบคำสั่ง ให้เปิด terminal จากปุ่ม **Terminal** ของ Laragon
หรือเพิ่ม `C:\laragon\bin\php\php-8.3.x-...` เข้า PATH

## ติดตั้งและรัน

```bash
# 1. ติดตั้ง dependency และสร้าง autoload
composer install

# 2. สร้างไฟล์ตั้งค่า แล้วแก้ค่าการเชื่อมต่อฐานข้อมูลใน .env
cp .env.example .env          # Windows (cmd): copy .env.example .env

# 3. สร้างฐานข้อมูล ตาราง และข้อมูลตัวอย่าง (ประมาณ 15 วินาที)
php database/migrate.php

# 4. รันเว็บ
php -S localhost:8000 -t public
```

เปิด <http://localhost:8000> แล้วเข้าสู่ระบบด้วยบัญชีทดสอบด้านล่าง

> ต้องใช้ `-t public` เสมอ เพื่อให้เปิดเผยเฉพาะโฟลเดอร์ `public/` ไฟล์ `.env` และโค้ดใน `src/` จะเข้าจากเว็บไม่ได้

### บัญชีทดสอบ

รหัสผ่านของทุกบัญชีเหมือนชื่อผู้ใช้ และหน้าเข้าสู่ระบบมีปุ่มกรอกให้อัตโนมัติ (แสดงเฉพาะเมื่อ `APP_ENV=local`)

| ชื่อผู้ใช้ | บทบาท | เห็นเมนู |
|---|---|---|
| `admin` | ผู้ดูแลระบบ | ทุกเมนู รวมผู้ใช้งาน แผนก บันทึกการใช้งาน และตั้งค่าระบบ |
| `officer` | เจ้าหน้าที่ฝ่ายบริหาร | อนุมัติการจอง มอบหมายงานซ่อม อนุมัติใบลา รายงาน |
| `manager` | ผู้บริหาร | อนุมัติการจอง อนุมัติใบลา รายงานและรายได้ |
| `technician` | นายช่างซ่อมบำรุง | หน้าสำหรับนายช่าง รับและปิดงานซ่อม |
| `employee` | บุคลากร | จองห้อง แจ้งซ่อม ลงเวลา ยื่นใบลา |

มีบัญชี `staff6`–`staff100` และนายช่าง `tech1`–`tech15` ด้วย (รหัสผ่านเหมือนชื่อผู้ใช้)

> **ก่อนใช้งานจริง** ให้เปลี่ยนรหัสผ่านของทุกบัญชีทดสอบ หรือสร้างฐานข้อมูลใหม่โดยไม่ใช้ข้อมูลตัวอย่าง และตั้ง `APP_ENV=production`, `APP_DEBUG=false`

## คำสั่งที่ใช้บ่อย

| คำสั่ง | ทำอะไร |
|---|---|
| `composer serve` | รันเว็บที่ localhost:8000 (เหมือน `php -S localhost:8000 -t public`) |
| `composer db:setup` | สร้างตารางที่ยังไม่มี อัปเกรดโครงสร้าง และใส่ข้อมูลตัวอย่างถ้ายังไม่มีผู้ใช้ |
| `composer db:fresh` | **ลบทุกตาราง** แล้วสร้างใหม่พร้อมข้อมูลตัวอย่าง |
| `composer lint` | ตรวจรูปแบบโค้ดตาม PSR-12 |
| `composer lint:fix` | แก้รูปแบบโค้ดอัตโนมัติ |
| `php tools/build-icons.php` | สร้างไฟล์ไอคอน `public/images/icons.svg` ใหม่ หลังเพิ่มชื่อไอคอนในสคริปต์ (ต้องต่ออินเทอร์เน็ต) |

## ค่าตั้งค่าใน `.env`

| ตัวแปร | ค่าเริ่มต้น | ความหมาย |
|---|---|---|
| `APP_NAME` | ระบบจองห้องประชุมศาลากลางจังหวัด | ชื่อที่แสดงในแท็บและท้ายหน้า |
| `APP_ENV` | `local` | `local` จะแสดงปุ่มบัญชีทดสอบในหน้าเข้าสู่ระบบ |
| `APP_DEBUG` | `true` | `true` แสดงรายละเอียดข้อผิดพลาด **ต้องเป็น `false` บนเครื่องจริง** |
| `APP_TIMEZONE` | `Asia/Bangkok` | เขตเวลาที่ใช้บันทึกเวลาเข้า-ออกงานและตรวจเวลาการจอง |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `127.0.0.1`, `3306`, `chowpad`, `root`, (ว่าง) | การเชื่อมต่อ MySQL (Laragon ใช้ root ไม่มีรหัสผ่าน) |
| `REALTIME_POLL_SECONDS` | `15` | ความถี่ที่หน้า dashboard และหน้าห้องประชุมดึงสถานะห้องใหม่ |
| `ANTHROPIC_API_KEY` | (ว่าง) | ใส่เพื่อเปิดโหมด AI ของผู้ช่วยตอบคำถาม (ว่าง = ตอบเองจากคำสำคัญ) |
| `CHAT_MODEL` | `claude-opus-5-5` | model ที่ใช้ตอบในโหมด AI |
| `CHAT_AI_PER_HOUR` | `30` | จำนวนคำถามโหมด AI ต่อผู้ใช้ต่อชั่วโมง |

## ผู้ช่วยตอบคำถาม (chatbot)

ปุ่มแชตมุมขวาล่างของทุกหน้า (หลังเข้าสู่ระบบ) ใช้ถามข้อมูลในระบบ ทำงานได้ 2 แบบ:

| โหมด | เมื่อไร | ตอบอะไรได้ |
|---|---|---|
| **ตอบเองจากคำสำคัญ** | ทำงานเสมอ ไม่ต้องใช้อินเทอร์เน็ต | ห้องว่างตอนนี้หรือตามวันเวลา (`ห้องว่างพรุ่งนี้ 13:00-15:00`), ตารางห้อง, การจองของฉัน, เลขที่ `BK-…` / `MNT-…`, รายการรออนุมัติ, วันลาคงเหลือ, การลงเวลา, วันหยุด, กฎการจองและค่าห้อง, รายชื่อห้อง, ภาพรวมระบบ, วิธีจอง/ยกเลิก |
| **AI (Claude)** | ใส่ `ANTHROPIC_API_KEY` ใน `.env` | คำถามอิสระที่โหมดแรกไม่เข้าใจ Claude ดึงข้อมูลผ่านชุดเครื่องมือเดียวกัน (`src/Services/Chat/ChatTools.php`) |

- **ข้อมูลและสิทธิ์:** ทุกเครื่องมืออ่านข้อมูลอย่างเดียวและตรวจสิทธิ์เหมือนหน้าเว็บ เช่น บุคลากรดูการจองของคนอื่นไม่ได้ และรายการรออนุมัติดูได้เฉพาะผู้อนุมัติ
- **ทำรายการแทนไม่ได้:** chatbot ไม่จอง ไม่ยกเลิก ไม่อนุมัติ แต่บอกขั้นตอนและลิงก์ไปหน้าที่ต้องทำ
- **ค่าตั้งของโหมด AI:**
  - model ตั้งได้ด้วย `CHAT_MODEL` (ค่าเริ่มต้น `claude-opus-5-5`, effort `low`)
  - จำกัดคนละ `CHAT_AI_PER_HOUR` คำถามต่อชั่วโมง
  - บันทึกเฉพาะจำนวน token ลงบันทึกการใช้งาน ไม่บันทึกข้อความคำถาม
- **เมื่อ Claude ปฏิเสธคำถาม:** โหมด AI เปิด server-side fallback (`fallbacks: "default"`) ไว้ ถ้า model หลักปฏิเสธด้วยเหตุผลด้านนโยบาย API จะส่งต่อให้ model สำรองตอบแทน
- **ความเป็นส่วนตัว:** เมื่อเปิดโหมด AI คำถามและข้อมูลที่เครื่องมือดึงมาจะถูกส่งไปประมวลผลที่ Anthropic ควรตรวจสอบนโยบายข้อมูลของหน่วยงานก่อนเปิดใช้

## โครงสร้างโปรเจกต์

```
public/            จุดเข้าเว็บจุดเดียว (document root)
  index.php        front controller: โหลด config, เปิด session, ส่งต่อให้ router
  css/             tokens · base · layout · components · utilities (ตาม DESIGN_SYSTEM.md)
  js/              app.js (ส่วนกลาง) และสคริปต์รายหน้า
  images/icons.svg ไอคอน Lucide แบบ sprite (ไม่ต้องโหลดจาก CDN)
  uploads/         รูปที่ผู้ใช้อัปโหลด (ไม่อยู่ใน git)
src/               namespace App\ (PSR-4)
  Controllers/     รับ request แล้ว render view หรือตอบ JSON
  Models/          คำสั่ง SQL ของแต่ละตาราง
  Services/        Router, Database (PDO), Auth, Csrf, View และ business logic (การจอง งานซ่อม การลา)
  helpers.php      e(), csrf_field(), icon(), thai_date() ฯลฯ
views/
  layouts/         app (มีเมนู), auth (หน้าเข้าสู่ระบบ), error
  partials/        head, topbar, sidebar, footer, flash, pagination, dialog ต่าง ๆ
  pages/           หน้าเว็บแต่ละหน้า
config/            app, database, roles (สิทธิ์ตามบทบาท), menu (เมนูด้านซ้าย), bootstrap
routes/web.php     กำหนด URL ทั้งหมด
database/          schema.sql, migrate.php และข้อมูลตัวอย่าง
tools/             สคริปต์สร้างไอคอน
```

### การทำงานของ request

```
เบราว์เซอร์ → public/index.php → Router (routes/web.php)
   ├─ ตรวจ CSRF ทุก request ที่ไม่ใช่ GET
   ├─ ตรวจการเข้าสู่ระบบ (ทุก route ต้องล็อกอิน ยกเว้นที่ระบุ auth => false)
   ├─ ตรวจบทบาท (กลุ่มบทบาทใน config/roles.php)
   └─ Controller → Service/Model (PDO) → View (layouts + partials + pages)
```

### เพิ่มหน้าใหม่

1. สร้าง controller ใน `src/Controllers/` (extends `Controller`) แล้วเรียก `$this->view('ชื่อหน้า', [...])`
2. สร้าง view ใน `views/pages/` โดยพิมพ์ค่าทุกค่าผ่าน `e()` และใส่ `<?= csrf_field() ?>` ในทุกฟอร์ม POST
3. เพิ่ม route ใน `routes/web.php` และกำหนดสิทธิ์ด้วย `['roles' => config('roles.xxx')]` ถ้าต้องจำกัด
4. ถ้าต้องการให้แสดงในเมนู ให้เพิ่มใน `config/menu.php`

## ความปลอดภัย

- **SQL:** ทุกคำสั่งใช้ PDO prepared statement (`ATTR_EMULATE_PREPARES = false`)
- **XSS:** ทุกค่าที่แสดงผ่าน `e()` (htmlspecialchars) และฝั่ง JS ใช้ `App.escapeHtml()` ก่อนใส่ `innerHTML`
- **CSRF:** ทุกฟอร์ม POST มี token และ router ตรวจเองที่จุดเดียว ส่วน `fetch()` ส่ง token ใน header `X-CSRF-Token`
- **รหัสผ่าน:** เก็บด้วย `password_hash()` มีความยาวขั้นต่ำ 8 ตัวอักษร และล็อกการเข้าสู่ระบบ 15 นาทีเมื่อผิด 5 ครั้งจาก IP เดียวกัน
- **Session:** cookie เป็น HttpOnly และ SameSite=Lax, สร้าง session id ใหม่หลังเข้าสู่ระบบและเปลี่ยนรหัสผ่าน
- **อัปโหลดรูป:** ตรวจชนิดจากเนื้อไฟล์ จำกัด 5 MB แล้ว encode ใหม่ด้วย GD ซึ่งตัดข้อมูล EXIF/GPS และเนื้อหาแฝงทิ้ง
- **สิทธิ์:** ตรวจที่ route และใน service (เช่น ช่างปิดงานได้เฉพาะงานของตัวเอง, อนุมัติใบลาของตัวเองไม่ได้)
- **ประวัติ:** การกระทำสำคัญทั้งหมดถูกบันทึกในบันทึกการใช้งาน (Audit Log)

## ใช้กับ Apache (Laragon แบบ virtual host)

ตั้ง document root ไปที่โฟลเดอร์ `public/` ไฟล์ `public/.htaccess` จะส่งทุก URL ไปที่ `index.php`
และ `public/uploads/.htaccess` จะกันการรันสคริปต์ในโฟลเดอร์อัปโหลด

## ที่มาของหน้าเว็บ (จากไฟล์ HTML เดิมใน `scratch/`)

| เดิม | ใหม่ |
|---|---|
| `provincial-hall-booking-system/login.html`, `index.html` | `/login`, `/` |
| `dashboard.html` | `/dashboard` (ตัวเลขและกราฟจากข้อมูลจริง + สถานะห้องเรียลไทม์) |
| `rooms.html`, `booking.html`, `calendar.html` | `/rooms`, `/bookings/create`, `/calendar` |
| `booking-list.html`, `booking-approval.html` | `/bookings`, `/bookings/{id}`, `/bookings/approval` |
| `maintenance.html`, `maintenance-list.html`, `technician.html` | `/maintenance/create`, `/maintenance`, `/maintenance/{id}`, `/technician` |
| `reports.html`, `revenue.html` | `/reports`, `/revenue` |
| `users.html`, `departments.html` | `/users`, `/departments` |
| `notifications.html`, `audit-log.html`, `profile.html` | `/notifications`, `/audit-logs`, `/profile` |
| `settings.html` และหน้าตั้งค่าย่อย 5 หน้า | `/settings`, `/settings/{rooms,holidays,leave-types,backup,system}` |
| `meeting-room-maintenance-dashboard/` | รวมเข้าระบบหลัก: ตรวจห้องว่างทุกห้อง, ตารางวันนี้, อัปโหลดรูปแจ้งซ่อม, กฎการจอง (`/settings/booking-rules`) |
| `employee-attendance-dashboard/` | `/attendance`, `/attendance/history`, `/attendance/reports`, `/leave`, `/leave/approval` |

## ข้อจำกัดที่ทราบ

- **"เรียลไทม์":** ใช้การดึงข้อมูลซ้ำทุก 15 วินาที เพราะ `php -S` รองรับ WebSocket ไม่ได้
- **ช่องวันที่และเวลา:** ใช้ input ของเบราว์เซอร์ ช่องกรอกจึงแสดงตาม locale ของเครื่อง ส่วนข้อความในหน้าแสดงเป็น พ.ศ. ทั้งหมด
- **ไฟล์สำรอง JSON:** ในหน้าตั้งค่าไม่รวมรูปที่อัปโหลด ระบบใช้งานจริงควรสำรองด้วย `mysqldump` และคัดลอกโฟลเดอร์ `public/uploads/` ด้วย
- **ยังไม่มี:** การส่งอีเมล, การจองซ้ำ (recurring), ตารางช่องเวลาแบบลากเลือก (DESIGN_SYSTEM 7.5) และการบันทึกขาดงานอัตโนมัติ
