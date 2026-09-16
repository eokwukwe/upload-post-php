# Upload-Post API Documentation

> Upload-Post is a unified social media posting API: publish videos, photos, text and documents to TikTok, Instagram, YouTube, LinkedIn, Facebook, X (Twitter), Threads, Pinterest, Reddit, Bluesky, Discord, Telegram and Google Business Profile with a single REST call (POST https://api.upload-post.com/api/upload). Includes scheduling, analytics, comments/DMs, webhooks, an FFmpeg API and white-label integration.

This file contains the full documentation of the Upload-Post API in markdown, one page per section. Each section starts with the page title and its canonical URL. Raw markdown for any single page is also available at <page URL>.md (e.g. https://docs.upload-post.com/api/upload-video.md).


---
# AI Shorts API
URL: https://docs.upload-post.com/api/ai-shorts

# AI Shorts API

The same AI that powers the dashboard's **Generate with AI** button is available through the API. Send a short-form video and receive platform-specific titles, descriptions, captions and hashtags, ready to pass to the [Upload Video](./upload-video.md) endpoint. A second endpoint rewrites captions you already have into another language.

Both endpoints share the monthly **AI Shorts quota** of your plan (see [AI Shorts Uploader](../guides/ai-shorts-uploader.md)); every successful call consumes one analysis.

## POST /api/uploadposts/analyze-shorts

### Headers

| Name | Value | Description |
|------|-------|-------------|
| Authorization | Apikey your-api-key-here | Your API key |
| Content-Type | multipart/form-data | |

### Parameters

| Name | Type | Required | Description |
|------|------|----------|-------------|
| video | File | Yes | The short-form video to analyze. Max **100 MB** and **5 minutes**. |
| platforms | String | No | Comma-separated list of `youtube`, `instagram`, `tiktok`, `facebook`. Default: `youtube,instagram,tiktok`. |
| language | String | No | Language for the generated text (e.g. `English`, `Spanish`, `Japanese`). When omitted, the AI detects the language spoken in the video and writes in that language. |

### Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/analyze-shorts \
  -H "Authorization: Apikey your-api-key-here" \
  -F "video=@/path/to/short.mp4" \
  -F "platforms=youtube,instagram,tiktok" \
  -F "language=English"
```

### Success Response

```json
{
  "success": true,
  "ai_generated": true,
  "remaining_analyses": 287,
  "youtube": {
    "title": "How I edit Shorts in 60 seconds",
    "description": "Full workflow, no fluff. #shorts #editing"
  },
  "instagram": {
    "caption": "60-second editing workflow ✂️ #reels #editing #creators"
  },
  "tiktok": {
    "caption": "editing shorts in 60s ⏱️ #editing #fyp #creatortips"
  }
}
```

Only the requested platforms are present. `remaining_analyses` is what is left of your monthly quota after this call.

### Error Responses

| Status | When |
|--------|------|
| 400 | Missing `video`, no valid platform, file over 100 MB or longer than 5 minutes |
| 401 | Invalid or missing API key |
| 429 | Monthly AI Shorts quota exhausted (`remaining_analyses: 0`) — resets at the start of your next billing period |
| 500 | The AI could not analyze the video; retry later (a failed analysis is not counted) |

## POST /api/uploadposts/rewrite-captions

Rewrites captions you already have (for example the output of `analyze-shorts`) into a target language, keeping each platform's style and hashtags. Consumes one analysis.

### Headers

| Name | Value |
|------|-------|
| Authorization | Apikey your-api-key-here |
| Content-Type | application/json |

### Body

| Name | Type | Required | Description |
|------|------|----------|-------------|
| content | Object | Yes | Current text per platform, in the same shape the analyze endpoint returns: `{"youtube": {"title", "description"}, "instagram": {"caption"}, "tiktok": {"caption"}, "facebook": {"caption"}}` |
| language | String | Yes | Target language (e.g. `Spanish`, `Japanese`). |
| platforms | Array | No | Subset of the platforms in `content` to rewrite. Default: all of them. |

### Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/rewrite-captions \
  -H "Authorization: Apikey your-api-key-here" \
  -H "Content-Type: application/json" \
  -d '{
    "language": "Spanish",
    "content": {
      "youtube": {"title": "How I edit Shorts in 60 seconds", "description": "Full workflow. #shorts"},
      "tiktok": {"caption": "editing shorts in 60s #editing #fyp"}
    }
  }'
```

The response has the same shape as `analyze-shorts` (`success`, `remaining_analyses` and one object per platform).

## Quotas

| Plan | Analyses / month |
|------|-----------------|
| Free | 10 |
| Professional | 300 |
| Advanced | 600 |
| Business | 1,000 |

## Related

- [AI Shorts Uploader guide](../guides/ai-shorts-uploader.md) — limits, best practices, FAQ
- [Upload Video](./upload-video.md) — publish the video with the generated text


---
# Audience Insights
URL: https://docs.upload-post.com/api/audience

# Audience Insights

[Get Analytics](./get-analytics.md) answers *how did my posts do*. This endpoint
answers the other half: **who follows this account, when they are online, what
they tap on the profile and how it compares to its category**.

It is one endpoint with a `platform`, exactly like
[`/comments`](./comments.md) and
[`/post-analytics`](./get-analytics.md#get-apiuploadpostspost-analyticsplatform_post_id):
the question is the same for every network, so the URL is too. Adding a network
never changes your integration — the same call starts answering for a new value
of `platform`.

The field to build a scheduler on is `activity_by_hour` — the number of
followers connected in each hour of the day, added up over the window. That is
the answer to "when should I publish", and it comes from your own audience
rather than from a generic best-time-to-post table.

-   **Method:** `GET`
-   **Endpoint:** `/api/uploadposts/audience`
-   **Authentication:**
    -   **API Key** in the `Authorization` header.
        -   `Authorization: Apikey <YOUR_API_KEY>`

### Query Parameters

| Parameter | Type | Required | Description | Default |
| :--- | :--- | :--- | :--- | :--- |
| `platform` | string | Yes | The network to ask. Today only `tiktok` answers; anything else is a `400` naming the platforms that do. | - |
| `user` | string | Yes | The profile's `username`. Must have an account of that platform connected. | - |
| `start_date` | string | No | First day of the window, ISO `YYYY-MM-DD`. | `end_date` − 29 days |
| `end_date` | string | No | Last day of the window, ISO `YYYY-MM-DD`. Must be **earlier than today**. | yesterday |
| `benchmark_category` | string | No | One of the 25 categories in `benchmark_categories`, case-insensitive. Adds the `benchmark` block to the answer. | - |

:::info Supported platforms
`platform=tiktok` today. Any other value answers `400` with
`error_code: "platform_not_supported"` and a message listing what is supported,
so a client can branch on the code instead of on a hard-coded list.
:::

**Window rules.** The platform refuses a window that ends today or later, and it
keeps at most **60 days** of history. Rather than forwarding a request that is
guaranteed to fail, Upload-Post trims what you asked for to what the platform
accepts: an `end_date` of today becomes yesterday, and a `start_date` older than
60 days becomes the oldest day available. **Render the `range` that comes back,
not the one you sent** — they are not always the same window. A value that is
not an ISO date is a hard `400`.

### Example Request

```bash
curl -G 'https://api.upload-post.com/api/uploadposts/audience' \
  -H 'Authorization: Apikey your-api-key-here' \
  -d 'platform=tiktok' \
  -d 'user=your_profile' \
  -d 'start_date=2026-08-01' \
  -d 'end_date=2026-08-30'
```

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "platform": "tiktok",
  "range": { "start_date": "2026-08-01", "end_date": "2026-08-30" },
  "audience": {
    "countries": [
      { "country": "ES", "percentage": 0.588 },
      { "country": "MX", "percentage": 0.161 },
      { "country": "AR", "percentage": 0.074 }
    ],
    "cities": [
      { "city": "Madrid", "percentage": 0.121 },
      { "city": "Barcelona", "percentage": 0.083 }
    ],
    "ages": [
      { "age": "18-24", "percentage": 0.372 },
      { "age": "25-34", "percentage": 0.411 },
      { "age": "35-44", "percentage": 0.145 }
    ],
    "genders": [
      { "gender": "female", "percentage": 0.624 },
      { "gender": "male", "percentage": 0.376 }
    ]
  },
  "activity_by_hour": [
    { "hour": "2", "followers_online": 456 },
    { "hour": "9", "followers_online": 981 },
    { "hour": "14", "followers_online": 1494 },
    { "hour": "21", "followers_online": 1327 }
  ],
  "followers_daily": [
    { "date": "2026-08-01", "total": 12480, "new": 63, "lost": 11 },
    { "date": "2026-08-02", "total": 12539, "new": 71, "lost": 12 }
  ],
  "profile_actions": {
    "bio_link_clicks": 318,
    "address_clicks": null,
    "app_download_clicks": null,
    "email_clicks": 12,
    "phone_number_clicks": null,
    "lead_submissions": null
  },
  "bio_description": "Corrijo exámenes con IA 📸",
  "benchmark_categories": [
    "PERSONAL_BLOG",
    "MACHINERY_AND_EQUIPMENT",
    "HEALTH_AND_WELLNESS",
    "PETS",
    "AUTOMOTIVE_AND_TRANSPORTATION",
    "EDUCATION_AND_TRAINING",
    "FOOD_AND_BEVERAGE",
    "REAL_ESTATE",
    "ELECTRONICS",
    "SHOPPING_AND_RETAIL",
    "PUBLIC_ADMINISTRATION",
    "ART_AND_CRAFTS",
    "BABY",
    "GAMING",
    "RESTAURANTS_AND_BARS",
    "HOME_FURNITURE_AND_APPLIANCES",
    "PROFESSIONAL_SERVICES",
    "SOFTWARE_AND_APPS",
    "MEDIA_AND_ENTERTAINMENT",
    "BEAUTY",
    "SPORTS_FITNESS_AND_OUTDOORS",
    "CLOTHING_AND_ACCESSORIES",
    "TRAVEL_AND_TOURISM",
    "OTHERS",
    "FINANCE_AND_INVESTING"
  ]
}
```

| Field | Description |
| :--- | :--- |
| `platform` | The network the answer came from, echoed back. |
| `range` | The window actually queried, **after trimming**. Read this one, not your input. |
| `audience.countries` / `.cities` / `.ages` / `.genders` | Follower distribution. `percentage` is a fraction of 1, not a number out of 100 — `0.588` means 58.8%. Percentages cannot be added across days, so each distribution is the most recent one reported inside the window. |
| `activity_by_hour` | Always 24 entries, `"0"`–`"23"` in the account's local time, ordered numerically so `9` comes before `10`. `followers_online` is the sum over the window: in the example the audience bottoms out at 456 at 2 a.m. and peaks at 1494 at 2 p.m., which is the hour to publish into. |
| `followers_daily` | One row per day, oldest first: `total` followers at the end of the day, `new` gained and `lost`. A field is `null` when the platform did not report it for that day. |
| `profile_actions` | Six counters totalled over the window: `bio_link_clicks`, `address_clicks`, `app_download_clicks`, `email_clicks`, `phone_number_clicks`, `lead_submissions`. **`null` is not `0`** — it means the platform answered nothing for this account, while `0` means it answered "nobody clicked". Do not coalesce one into the other in a dashboard. |
| `bio_description` | The account's current bio text. |
| `benchmark_categories` | The 25 values `benchmark_category` accepts. Always present, so a UI can build its picker without a second call. |

### With `benchmark_category`

Averages tell you whether 3% engagement is good *for what this account
publishes*. A number on its own means nothing; this is the comparison that gives
it meaning.

```bash
curl -G 'https://api.upload-post.com/api/uploadposts/audience' \
  -H 'Authorization: Apikey your-api-key-here' \
  -d 'platform=tiktok' \
  -d 'user=your_profile' \
  -d 'benchmark_category=SOFTWARE_AND_APPS'
```

The same body as above, plus:

```json
{
  "benchmark": {
    "category": "SOFTWARE_AND_APPS",
    "average_comments": 19,
    "average_engagement_rate": 0.0144,
    "average_follower_count": 24713,
    "average_follower_growth": 186,
    "average_likes": 742,
    "average_shares": 43,
    "average_video_count": 17,
    "average_video_views": 21508
  }
}
```

| Field | Description |
| :--- | :--- |
| `benchmark.category` | The category you asked for, normalised to upper case. |
| `benchmark.average_engagement_rate` | Fraction of 1 — `0.0144` means 1.44%, the average for `SOFTWARE_AND_APPS`. |
| `benchmark.average_likes` / `average_comments` / `average_shares` | Per post, on average, for accounts in that category. |
| `benchmark.average_video_views` | Average views per video. |
| `benchmark.average_follower_count` / `average_follower_growth` | Account size and growth in the period the platform measures. |
| `benchmark.average_video_count` | How many videos a typical account in the category publishes in the period. |

Only the metrics the platform actually returned are present, so read the keys
you need defensively rather than assuming all eight are there. The category is
**not** stored on the profile: pass it on every call, which also lets you
compare one account against two categories when it straddles both.

### Error Responses

- `400 Bad Request` — the platform does not answer this question yet. The
  message names the ones that do.

```json
{
  "success": false,
  "message": "'instagram' does not support audience insights yet. Supported: tiktok.",
  "error_code": "platform_not_supported"
}
```

- `400 Bad Request` — a date that is not ISO `YYYY-MM-DD`, a `benchmark_category`
  outside the 25 values, or a missing `platform`.

```json
{
  "success": false,
  "message": "end_date must be an ISO date (YYYY-MM-DD).",
  "error_code": "invalid_parameter"
}
```

- `400 Bad Request` — the profile's connection cannot serve this endpoint.
  Reconnect the account.

```json
{
  "success": false,
  "message": "Profile 'your_profile' has no TikTok connection that supports this endpoint. Reconnect your TikTok account from Manage Users.",
  "error_code": "tiktok_reconnect_required"
}
```

- `404 Not Found` — no profile with that username exists under your API key
  (`error_code: "PROFILE_NOT_FOUND"`).
- `409 Conflict` — the token expired and the account must be reconnected
  (`"reauth_required": true`).
- `502 Bad Gateway` — the platform rejected the lookup; its message is returned
  verbatim in `message`.

### Additional Notes

- **Which connections answer.** On TikTok, the audience, the benchmark and
  [hashtag suggestions](./suggestions.md) work on **any recently connected
  account** (the `profile_analytics`
  [capability](./user-profiles.md#account-capabilities)). Only
  [comments](./comments.md) and `type=keywords` on
  [Suggestions](./suggestions.md) need the account to be **reconnected** — the
  `comments` and `trend_search` capabilities are granted at the moment of
  connecting, so an older connection does not carry them.
- Percentages come from the platform as fractions of 1. Multiply by 100 before
  rendering.
- `audience.cities` is the noisiest breakdown: small accounts often get an empty
  array while countries and ages are populated.
- A brand-new connection has no history yet. Expect empty arrays for the first
  days rather than an error.

---

## Related

- [Suggestions](./suggestions.md) — the hashtags and search terms to write the
  next post with.
- [Get Analytics](./get-analytics.md) — cross-platform account and per-post
  metrics, including the full TikTok per-post breakdown (retention, impression
  sources, audience types).
- [Account capabilities](./user-profiles.md#account-capabilities).


---
# AutoDM Monitors
URL: https://docs.upload-post.com/api/autodms

# AutoDM Monitors

Set up persistent monitors that automatically send private DMs to users who comment on your Instagram posts. Monitors run in the background 24/7 — no need to keep polling manually.

---

## Start a Monitor

Create a new AutoDM monitor for an Instagram post. The monitor will check for new comments at regular intervals and send a private reply (DM) to each new commenter.

### Endpoint

```
POST /api/uploadposts/autodms/start
```

### Headers

| Name          | Value                    | Description                     |
|---------------|--------------------------|---------------------------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Body Parameters (JSON)

| Name                | Type           | Required | Description                                                                                     |
|---------------------|----------------|----------|-------------------------------------------------------------------------------------------------|
| post_url            | String         | Yes      | The Instagram post URL to monitor for comments.                                                |
| reply_message       | String         | Yes      | The DM message to send to each matching commenter.                                             |
| profile_username    | String         | Yes      | Profile username (as configured in Upload-Post). Must have Instagram connected.                |
| buttons             | Array          | No       | Up to 3 `web_url` buttons added to each auto-DM. Each item is an object `{title, url}` (title max 20 chars, url must be http/https). |
| monitoring_interval | Integer        | No       | Minutes between comment checks. Default: `15`. Minimum: `15`.                                 |
| trigger_keywords    | Array / String | No       | Keywords to filter comments. Only comments containing at least one keyword will receive a DM. Case-insensitive and accent-insensitive (`"guide"` matches "GUIDE", "guía", "güide", etc.). If omitted, all commenters receive a DM. Accepts a single string or an array of strings. |

### Limits

- **2 new monitors per profile per day.** You can create up to 2 monitors per profile in a 24-hour period.
- **No duplicate posts.** If there's already an active monitor for a post URL, you must stop it before creating a new one.
- **Auto-expiration.** Monitors automatically stop after **15 days**.
- **Daily DM limits per plan.** Free: 10 DMs/day. Paid: 500 DMs/day. When the limit is reached, the monitor pauses and resumes the next day.

### Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/autodms/start \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "post_url": "https://www.instagram.com/p/ABC123/",
    "reply_message": "Hey! Here is your free guide: https://example.com/guide",
    "profile_username": "my-profile",
    "monitoring_interval": 15,
    "trigger_keywords": ["guide", "link"],
    "buttons": [
      { "title": "Get the guide", "url": "https://example.com/guide" }
    ]
  }'
```

### Responses

- **200 OK** (monitor started)

```json
{
  "success": true,
  "message": "AutoDMs monitoring started successfully",
  "monitor_id": "user@example.com_my-profile_1234567890",
  "config": {
    "post_url": "https://www.instagram.com/p/ABC123/",
    "reply_message": "Hey! Here is your free guide: https://example.com/guide",
    "profile_username": "my-profile",
    "monitoring_interval": 15
  }
}
```

- **400 Bad Request** (missing fields, no Instagram connected, or duplicate post)

```json
{
  "success": false,
  "error": "There is already an active monitor for this post. Stop it first before creating a new one."
}
```

- **429 Too Many Requests** (daily limit reached)

```json
{
  "success": false,
  "error": "Limit reached: maximum 2 monitors per profile per day."
}
```

---

## Get Monitor Status

Retrieve the status of AutoDM monitors for your account. By default returns active monitors (`running` / `paused`). Pass `include_inactive=true` to also receive stopped and expired monitors so you can recover monitor IDs without keeping your own database.

### Endpoint

```
GET /api/uploadposts/autodms/status
```

### Headers

| Name          | Value                    | Description                     |
|---------------|--------------------------|---------------------------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Query Parameters

| Name             | Type   | Required | Description                                                                                                       |
|------------------|--------|----------|-------------------------------------------------------------------------------------------------------------------|
| include_inactive | Bool   | No       | When `true`, also returns stopped and expired monitors. Deleted monitors are always excluded. Default: `false`.   |

### Example Requests

```bash
# Default — active monitors only
curl 'https://api.upload-post.com/api/uploadposts/autodms/status' \
  -H 'Authorization: Apikey your-api-key-here'

# All monitors (active + stopped + expired)
curl 'https://api.upload-post.com/api/uploadposts/autodms/status?include_inactive=true' \
  -H 'Authorization: Apikey your-api-key-here'
```

### Responses

- **200 OK**

```json
{
  "success": true,
  "monitors": [
    {
      "monitor_id": "user@example.com_my-profile_1234567890",
      "post_url": "https://www.instagram.com/p/ABC123/",
      "reply_message": "Hey! Here is your free guide: https://example.com/guide",
      "profile_username": "my-profile",
      "monitoring_interval": 15,
      "is_active": true,
      "is_running": true,
      "is_paused": false,
      "status": "running",
      "stats": {
        "total_comments": 47,
        "new_comments": 5,
        "successful_replies": 31,
        "failed_replies": 2
      },
      "created_at": "2026-03-28T10:00:00",
      "last_check": "2026-03-28T14:30:00",
      "paused_at": null,
      "stopped_at": null,
      "stop_reason": null
    },
    {
      "monitor_id": "user@example.com_my-profile_1234567000",
      "post_url": "https://www.instagram.com/p/XYZ987/",
      "reply_message": "Hey! Here is your free guide: https://example.com/guide",
      "profile_username": "my-profile",
      "monitoring_interval": 15,
      "is_active": false,
      "is_running": false,
      "is_paused": false,
      "status": "stopped",
      "stats": {
        "total_comments": 120,
        "new_comments": 0,
        "successful_replies": 88,
        "failed_replies": 3
      },
      "created_at": "2026-03-20T10:00:00",
      "last_check": "2026-03-27T18:00:00",
      "paused_at": null,
      "stopped_at": "2026-03-27T18:05:00",
      "stop_reason": null
    }
  ],
  "total_active": 1,
  "total": 2
}
```

**Status values:** `running`, `paused`, `resuming`, `stopped`, `expired`

- `running` — thread is currently checking comments and replying.
- `paused` — temporarily halted (data preserved, can be resumed).
- `resuming` — monitor was active in DB but no live thread; one was just started.
- `stopped` — manually stopped via `POST /autodms/stop`. Only returned when `include_inactive=true`.
- `expired` — auto-stopped after the 15-day lifetime. Only returned when `include_inactive=true`.

**Response fields:**

- `total_active` — count of monitors with status `running`, `paused`, or `resuming`. Unchanged by `include_inactive` so existing dashboards keep working.
- `total` — count of every monitor in the response.
- `stopped_at` / `stop_reason` — only set for monitors with `is_active: false`.

---

## Get Monitor Logs

Retrieve activity logs for a specific monitor.

### Endpoint

```
GET /api/uploadposts/autodms/logs
```

### Headers

| Name          | Value                    | Description                     |
|---------------|--------------------------|---------------------------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Query Parameters

| Name       | Type   | Required | Description              |
|------------|--------|----------|--------------------------|
| monitor_id | String | Yes      | The monitor ID to query. |

### Example Request

```bash
curl 'https://api.upload-post.com/api/uploadposts/autodms/logs?monitor_id=user@example.com_my-profile_1234567890' \
  -H 'Authorization: Apikey your-api-key-here'
```

### Responses

- **200 OK**

```json
{
  "success": true,
  "logs": [
    {
      "type": "start",
      "timestamp": "2026-03-28T10:00:00",
      "message": "Monitor started"
    },
    {
      "type": "success",
      "timestamp": "2026-03-28T10:15:00",
      "message": "31 DMs sent successfully"
    }
  ],
  "monitor_info": {
    "post_url": "https://www.instagram.com/p/ABC123/",
    "profile_username": "my-profile",
    "is_active": true
  }
}
```

---

## Pause a Monitor

Temporarily pause a monitor without losing its configuration. The monitor stops checking for comments but can be resumed later.

### Endpoint

```
POST /api/uploadposts/autodms/pause
```

### Headers

| Name          | Value                    | Description                     |
|---------------|--------------------------|---------------------------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Body Parameters (JSON)

| Name       | Type   | Required | Description              |
|------------|--------|----------|--------------------------|
| monitor_id | String | Yes      | The monitor ID to pause. |

### Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/autodms/pause \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{"monitor_id": "user@example.com_my-profile_1234567890"}'
```

### Responses

- **200 OK**

```json
{
  "success": true,
  "message": "Monitor paused successfully"
}
```

---

## Resume a Monitor

Resume a previously paused monitor. It will continue checking for new comments from where it left off.

### Endpoint

```
POST /api/uploadposts/autodms/resume
```

### Headers

| Name          | Value                    | Description                     |
|---------------|--------------------------|---------------------------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Body Parameters (JSON)

| Name       | Type   | Required | Description               |
|------------|--------|----------|---------------------------|
| monitor_id | String | Yes      | The monitor ID to resume. |

### Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/autodms/resume \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{"monitor_id": "user@example.com_my-profile_1234567890"}'
```

### Responses

- **200 OK**

```json
{
  "success": true,
  "message": "Monitor resumed successfully"
}
```

---

## Stop a Monitor

Deactivate a monitor. The monitor stops running but its data is preserved.

### Endpoint

```
POST /api/uploadposts/autodms/stop
```

### Headers

| Name          | Value                    | Description                     |
|---------------|--------------------------|---------------------------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Body Parameters (JSON)

| Name       | Type   | Required | Description             |
|------------|--------|----------|-------------------------|
| monitor_id | String | Yes      | The monitor ID to stop. |

### Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/autodms/stop \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{"monitor_id": "user@example.com_my-profile_1234567890"}'
```

### Responses

- **200 OK**

```json
{
  "success": true,
  "message": "Monitor stopped successfully"
}
```

---

## Delete a Monitor

Permanently delete a monitor and all its data.

### Endpoint

```
POST /api/uploadposts/autodms/delete
```

### Headers

| Name          | Value                    | Description                     |
|---------------|--------------------------|---------------------------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Body Parameters (JSON)

| Name       | Type   | Required | Description               |
|------------|--------|----------|---------------------------|
| monitor_id | String | Yes      | The monitor ID to delete. |

### Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/autodms/delete \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{"monitor_id": "user@example.com_my-profile_1234567890"}'
```

### Responses

- **200 OK**

```json
{
  "success": true,
  "message": "Monitor deleted successfully"
}
```

- **404 Not Found**

```json
{
  "success": false,
  "error": "Monitor not found or not owned by this account."
}
```

---

## Important Notes

1. **Instagram only.** AutoDM monitors currently support Instagram only, using Meta's official [Private Replies API](https://developers.facebook.com/docs/instagram-platform/private-replies/).

2. **One DM per comment.** Each comment can only receive one private reply. Duplicate attempts are automatically prevented.

3. **7-day comment window.** Private replies can only be sent to comments less than 7 days old.

4. **Daily DM limits.** Upload-Post enforces daily DM limits per account (varies by plan). When the limit is reached, the monitor pauses and resumes the next day.

5. **Auto-expiration.** Monitors automatically stop after 15 days to prevent stale monitors from running indefinitely.

6. **Rate limits.** Meta enforces a limit of 200 DMs per hour per Instagram account. The monitor includes built-in delays between DMs to stay within limits.

### How It Works

1. You start a monitor with a post URL and reply message.
2. Every `monitoring_interval` minutes, the monitor checks for new comments.
3. For each new comment, it sends a private DM using Meta's Private Replies API.
4. It tracks which comments have already been replied to, avoiding duplicates. Meta allows a single private reply per comment, so a comment that already received one is marked as done and never retried. Comments written by the monitored account itself are ignored.
5. After 15 days, the monitor automatically stops.

### Related Endpoints

- [Instagram Comments](./instagram-comments.md) — Read comments and send one-off private replies manually.
- [Instagram Direct Messages](./instagram-dms.md) — Send follow-up DMs and read conversations.
- [Media List](./instagram-media.md) — Find post IDs and URLs for your Instagram content.


---
# ChatGPT Ads
URL: https://docs.upload-post.com/api/chatgpt-ads

# ChatGPT Ads

Create and manage **ads inside ChatGPT** with the same API key you already use for publishing. Upload-Post talks to the OpenAI Advertiser API on your behalf, so you connect your ad account once and then drive campaigns, ad groups, ads, creatives and reporting through `api.upload-post.com`.

:::info What you need
An **OpenAI Ads Manager** account with API access. Issue a key at [ads.openai.com](https://ads.openai.com) → **Settings → API keys**. Each OpenAI key is scoped to exactly one ad account; connect one key per ad account you want to manage.
:::

**Spend runs on your own OpenAI billing.** Upload-Post never charges for ad spend and never bills through your ad account — it only relays the calls.

---

## How ChatGPT Ads are structured

```
Campaign        budget, schedule, location and audience targeting
└── Ad group    bid (max CPM or max CPC)
    └── Ad      creative: title, body, image, destination URL
```

An ad only serves when **all three** objects are `active` **and** the ad has passed OpenAI's review (`review_status: "approved"`, usually a few minutes).

:::warning Everything is created paused
Every create endpoint defaults to `status: "paused"` so nothing starts spending by accident. Send `"status": "active"` explicitly, or flip the objects later with the `/activate` actions.
:::

---

## Connect an ad account

### Endpoint

```
POST /api/uploadposts/ads/chatgpt/connect
```

### Body Parameters (JSON)

| Name      | Type    | Required | Description                                                                       |
| :-------- | :------ | :------- | :-------------------------------------------------------------------------------- |
| `api_key` | String  | Yes      | Your OpenAI Ads API key. Validated live against OpenAI before it is stored.       |
| `profile_username` | String | No | Link the ad account to this profile. See [Profiles](#profiles-and-ad-accounts).   |
| `name`    | String  | No       | Friendly label. Defaults to the ad account name reported by OpenAI.               |
| `default` | Boolean | No       | Make this the account used when a request omits `ad_account_id`.                   |

### Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/ads/chatgpt/connect \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{"api_key": "your-openai-ads-api-key"}'
```

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "message": "ChatGPT Ads account connected successfully",
  "account": {
    "ad_account_id": "adacct_123",
    "name": "Acme Ads",
    "currency_code": "USD",
    "timezone": "UTC",
    "status": "active",
    "review_status": "approved",
    "default": true,
    "connected_at": "2026-09-06T10:12:00+00:00"
  }
}
```

The key is encrypted at rest and is **never returned** by any endpoint. Re-posting the same account replaces the stored key — that is how you rotate it.

### Managing connected accounts

| Method   | Endpoint                                              | Description                                                          |
| :------- | :---------------------------------------------------- | :------------------------------------------------------------------- |
| `GET`    | `/api/uploadposts/ads/chatgpt/accounts`               | Connected ad accounts (no credentials), each with the `profiles` it is linked to. |
| `GET`    | `/api/uploadposts/ads/chatgpt/account`                | Live metadata for the selected account, straight from OpenAI.        |
| `DELETE` | `/api/uploadposts/ads/chatgpt/accounts/{ad_account_id}` | Forget the stored key and unlink every profile. Campaigns keep running in Ads Manager. |
| `DELETE` | `/api/uploadposts/ads/chatgpt/accounts/{ad_account_id}?profile_username=...` | Unlink just that profile; the key stays for the others. |

### Profiles and ad accounts {#profiles-and-ad-accounts}

An ad account can be **linked to a profile**, which is how an agency ties each
client's advertising to that client's profile — the same place you connect their
social accounts. Pass `profile_username` when connecting, and then on any request:

```bash
curl -G https://api.upload-post.com/api/uploadposts/ads/chatgpt/campaigns \
  -H 'Authorization: Apikey your-api-key-here' \
  --data-urlencode 'profile_username=client-a'
```

:::warning No silent fallback
When a request names a profile, only that profile's linked ad account is used. If
the profile has no ad account linked the call returns `404` — it never falls back
to your default account, because spending on the wrong client's budget is worse
than an error.
:::

The link lives in the profile's own `ads_accounts` field, never in
`social_accounts`: an ad account is not a publishing destination and never
appears as an upload target.

### Choosing the ad account

Without a `profile_username`, pick one per request in any of these ways (first match wins):

1. `?ad_account_id=adacct_123` query parameter
2. `X-Ads-Account-Id: adacct_123` header
3. `"ad_account_id": "adacct_123"` in the JSON body

Otherwise the account flagged `default` is used; if there is no default and more than one account, the request returns `400`.

---

## Launch an ad in one call

`promote` creates the campaign, ad group and ad — and uploads the creative image — in a single request. Use it to promote a post you just published: pass the post's image as `image_url` and its permalink (or your landing page) as `target_url`.

### Endpoint

```
POST /api/uploadposts/ads/chatgpt/promote
```

### Body Parameters (JSON)

| Name             | Type          | Required | Description                                                                                     |
| :--------------- | :------------ | :------- | :---------------------------------------------------------------------------------------------- |
| `title`          | String        | Yes      | Ad headline, 3–50 characters.                                                                   |
| `body`           | String        | Yes      | Ad copy, up to 100 characters.                                                                  |
| `target_url`     | String        | Yes      | Destination URL (`http(s)`).                                                                    |
| `image_url`      | String        | Yes\*    | Creative image URL. OpenAI fetches it.                                                          |
| `file_id`        | String        | Yes\*    | Already-uploaded creative id. Alternative to `image_url`.                                       |
| `budget`         | Number        | Yes\*\*  | Lifetime budget in your ad account currency. Minimum `1`.                                        |
| `budget_micros`  | Integer       | Yes\*\*  | Same value in micros (millionths). `25000000` = 25.00. Alternative to `budget`.                  |
| `max_bid`        | Number        | Yes\*\*\* | Max bid per billing event in account currency (e.g. `0.06` for a $60 CPM).                       |
| `max_bid_micros` | Integer       | Yes\*\*\* | Same value in micros. Alternative to `max_bid`.                                                  |
| `name`           | String        | No       | Base name for the three objects. Defaults to `title`.                                            |
| `status`         | String        | No       | `paused` (default) or `active`.                                                                  |
| `bidding_type`   | String        | No       | `impressions` (default), `clicks` or `conversions`.                                              |
| `start_time`     | Integer/String| No       | Unix timestamp or ISO-8601 datetime. Starts immediately when omitted.                            |
| `end_time`       | Integer/String| No       | Unix timestamp or ISO-8601 datetime.                                                             |
| `locations`      | String[]      | No       | OpenAI location ids (country, region or DMA). All locations when omitted.                        |
| `context_hints`  | String[]      | No       | Free-form hints about when your ad is useful, e.g. `["productivity", "team collaboration"]`.     |
| `ad_group_name`  | String        | No       | Override the ad group name.                                                                      |
| `ad_name`        | String        | No       | Override the ad name.                                                                            |

\* One of `image_url` or `file_id`. \*\* One of `budget` or `budget_micros`. \*\*\* One of `max_bid` or `max_bid_micros`.

### Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/ads/chatgpt/promote \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Spring launch",
    "title": "Try the workspace planner",
    "body": "Coordinate tasks, docs and meetings in one place.",
    "target_url": "https://acme.example/planner",
    "image_url": "https://acme.example/assets/planner-card.png",
    "budget": 25,
    "max_bid": 0.06,
    "context_hints": ["productivity", "team collaboration"],
    "status": "active"
  }'
```

### Successful Response (`201 Created`)

```json
{
  "success": true,
  "campaign": { "id": "cmpn_101", "status": "active" },
  "ad_group": { "id": "adgrp_301", "status": "active" },
  "ad": { "id": "ad_501", "status": "active", "review_status": "in_review" },
  "status": "active",
  "message": "Ad created. It starts serving once OpenAI's review approves it (check 'review_status')."
}
```

Everything is built paused and only activated once all three objects exist. If a later step fails, the response carries what was already created so you can finish or archive it:

```json
{
  "success": false,
  "message": "bid above the account maximum",
  "details": { "created": { "campaign_id": "cmpn_101" } }
}
```

---

## Campaigns

| Method | Endpoint                                                    | Description                          |
| :----- | :---------------------------------------------------------- | :------------------------------------ |
| `GET`  | `/api/uploadposts/ads/chatgpt/campaigns`                    | List campaigns.                       |
| `POST` | `/api/uploadposts/ads/chatgpt/campaigns`                    | Create a campaign.                    |
| `GET`  | `/api/uploadposts/ads/chatgpt/campaigns/{campaign_id}`      | Retrieve one campaign.                |
| `POST` | `/api/uploadposts/ads/chatgpt/campaigns/{campaign_id}`      | Update a campaign.                    |
| `POST` | `/api/uploadposts/ads/chatgpt/campaigns/{campaign_id}/activate` \| `/pause` \| `/archive` | Change state. |

List endpoints accept `limit` (1–500, default 20), `after`, `before` and `order` (`asc`/`desc`).

### Create Body Parameters (JSON)

| Name                           | Type           | Required | Description                                                                     |
| :----------------------------- | :------------- | :------- | :------------------------------------------------------------------------------- |
| `name`                         | String         | Yes      | 3–1000 characters.                                                              |
| `budget` / `budget_micros`     | Number/Integer | Yes      | Lifetime spend limit. Minimum `1` (`1000000` micros).                            |
| `status`                       | String         | No       | `paused` (default) or `active`.                                                  |
| `description`                  | String         | No       | Free text.                                                                       |
| `start_time` / `end_time`      | Integer/String | No       | Unix timestamp or ISO-8601 datetime.                                             |
| `bidding_type`                 | String         | No       | `impressions` (default), `clicks` or `conversions`. Cannot be changed later.     |
| `conversion_event_setting_ids` | String[]       | No       | Exactly one active standard event setting, required for `conversions` bidding.   |
| `locations`                    | String[]       | No       | Shorthand for `targeting.locations.include`.                                     |
| `custom_audience_ids`          | String[]       | No       | Shorthand for `targeting.custom_audiences.ids`.                                  |
| `excluded_custom_audience_ids` | String[]       | No       | Shorthand for `targeting.excluded_custom_audiences.ids`.                          |
| `targeting`                    | Object         | No       | Full OpenAI targeting object. Overrides the shorthands above.                     |

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/ads/chatgpt/campaigns \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Spring launch",
    "budget": 25,
    "locations": ["2000043"],
    "status": "paused"
  }'
```

```json
{
  "success": true,
  "campaign": {
    "id": "cmpn_101",
    "name": "Spring launch",
    "status": "paused",
    "bidding_type": "impressions",
    "budget": { "lifetime_spend_limit_micros": 25000000 }
  }
}
```

On update, every field is optional and `status` also accepts `archived`. **Archiving is irreversible.**

---

## Ad groups

| Method | Endpoint                                                   | Description                                   |
| :----- | :--------------------------------------------------------- | :--------------------------------------------- |
| `GET`  | `/api/uploadposts/ads/chatgpt/adgroups?campaign_id=...`    | List ad groups (`campaign_id` is required).   |
| `POST` | `/api/uploadposts/ads/chatgpt/adgroups`                    | Create an ad group.                            |
| `GET`  | `/api/uploadposts/ads/chatgpt/adgroups/{ad_group_id}`      | Retrieve one ad group.                         |
| `POST` | `/api/uploadposts/ads/chatgpt/adgroups/{ad_group_id}`      | Update an ad group.                            |
| `POST` | `/api/uploadposts/ads/chatgpt/adgroups/{ad_group_id}/activate` \| `/pause` \| `/archive` | Change state. |

### Create Body Parameters (JSON)

| Name                           | Type           | Required | Description                                                                   |
| :----------------------------- | :------------- | :------- | :----------------------------------------------------------------------------- |
| `campaign_id`                  | String         | Yes      | Parent campaign.                                                              |
| `name`                         | String         | Yes      | 3–1000 characters.                                                            |
| `max_bid` / `max_bid_micros`   | Number/Integer | Yes      | Max bid per billing event. `0.06` = a $60 CPM on impression billing.          |
| `billing_event_type`           | String         | No       | `impression` (default) for impression campaigns, `click` for click/conversion. |
| `status`                       | String         | No       | `paused` (default) or `active`.                                                |
| `context_hints`                | String[]       | No       | When your product or service is useful, in your own words.                     |
| `description`                  | String         | No       | Free text.                                                                     |
| `bidding_config`               | Object         | No       | Full OpenAI bidding config. Overrides `max_bid` / `billing_event_type`.        |
| `product_set`                  | Object         | No       | Product filters for product-feed campaigns.                                    |

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/ads/chatgpt/adgroups \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "campaign_id": "cmpn_101",
    "name": "US English",
    "max_bid": 0.06,
    "context_hints": ["productivity", "team collaboration"]
  }'
```

---

## Creatives

Upload the image your ad uses and reuse the returned `file_id`.

```
POST /api/uploadposts/ads/chatgpt/creatives
```

Send either JSON with an `image_url`, or `multipart/form-data` with a binary `file` (up to 10 MB).

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/ads/chatgpt/creatives \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{"image_url": "https://acme.example/assets/planner-card.png"}'
```

```json
{ "success": true, "creative": { "file_id": "file_901" } }
```

---

## Ads

| Method | Endpoint                                                | Description                                       |
| :----- | :------------------------------------------------------ | :------------------------------------------------- |
| `GET`  | `/api/uploadposts/ads/chatgpt/ads?ad_group_id=...`      | List ads (`ad_group_id` is required).             |
| `POST` | `/api/uploadposts/ads/chatgpt/ads`                      | Create an ad.                                      |
| `GET`  | `/api/uploadposts/ads/chatgpt/ads/{ad_id}`              | Retrieve one ad.                                   |
| `POST` | `/api/uploadposts/ads/chatgpt/ads/{ad_id}`              | Update an ad.                                      |
| `POST` | `/api/uploadposts/ads/chatgpt/ads/{ad_id}/preview`      | Temporary iframe preview (expires in 24 h).       |
| `POST` | `/api/uploadposts/ads/chatgpt/ads/{ad_id}/activate` \| `/pause` \| `/archive` | Change state.    |

### Create Body Parameters (JSON)

| Name          | Type   | Required | Description                                                                 |
| :------------ | :----- | :------- | :--------------------------------------------------------------------------- |
| `ad_group_id` | String | Yes      | Parent ad group.                                                            |
| `name`        | String | Yes      | Internal name, 3–1000 characters. Not shown to users.                        |
| `title`       | String | Yes      | Headline, 3–50 characters.                                                   |
| `body`        | String | Yes      | Copy, up to 100 characters.                                                  |
| `target_url`  | String | Yes      | Destination URL (`http(s)`).                                                 |
| `file_id`     | String | Yes      | Creative id from `/creatives`.                                               |
| `status`      | String | No       | `paused` (default) or `active`.                                              |
| `creative`    | Object | No       | Full OpenAI creative object. Use it for `product_ad_template` ads.           |

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/ads/chatgpt/ads \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "ad_group_id": "adgrp_301",
    "name": "Planner launch card",
    "title": "Try the workspace planner",
    "body": "Coordinate tasks, docs and meetings in one place.",
    "target_url": "https://acme.example/planner",
    "file_id": "file_901",
    "status": "active"
  }'
```

```json
{
  "success": true,
  "ad": {
    "id": "ad_501",
    "name": "Planner launch card",
    "status": "active",
    "review_status": "in_review",
    "creative": {
      "type": "chat_card",
      "title": "Try the workspace planner",
      "body": "Coordinate tasks, docs and meetings in one place.",
      "image_url": "https://cdn.openai.com/ads/file_901.png",
      "target_url": "https://acme.example/planner"
    }
  }
}
```

`review_status` is `in_review`, `approved` or `rejected`. A rejected ad breaks OpenAI's [ads policies](https://openai.com/policies/ad-policies/); edit it and it goes back into review.

---

## Insights

| Endpoint                                                          | Scope        |
| :----------------------------------------------------------------- | :------------ |
| `GET /api/uploadposts/ads/chatgpt/insights`                       | Ad account   |
| `GET /api/uploadposts/ads/chatgpt/campaigns/{campaign_id}/insights` | Campaign     |
| `GET /api/uploadposts/ads/chatgpt/adgroups/{ad_group_id}/insights`  | Ad group     |
| `GET /api/uploadposts/ads/chatgpt/ads/{ad_id}/insights`            | Ad           |

### Query Parameters

| Name                | Description                                                                                    |
| :------------------ | :---------------------------------------------------------------------------------------------- |
| `since` / `until`   | `YYYY-MM-DD` shorthand for a date range in the ad account timezone.                             |
| `time_granularity`  | `hourly`, `daily` (default), `monthly` or `none`.                                               |
| `aggregation_level` | `ad_account`, `campaign`, `ad_group` or `ad` — the row entity inside the endpoint's scope.       |
| `fields`            | Repeatable. Metrics and metadata to project: `impressions`, `clicks`, `spend`, `ctr`, `cpc`, `cpm`, `campaign.name`, … |
| `segments`          | One of `product`, `country`, `device`.                                                          |
| `filters` / `sort`  | Repeatable JSON-encoded objects, passed through to OpenAI.                                       |
| `limit`             | 1–2000, default 20. Page with `after` / `before`.                                                |
| `time_ranges`       | Repeatable JSON-encoded range objects, for windows `since`/`until` can't express.                |

Repeatable parameters accept either spelling — `fields=clicks&fields=spend` or
`fields[]=clicks&fields[]=spend` — and are always forwarded to OpenAI in the
`[]` form it expects.

```bash
curl -G https://api.upload-post.com/api/uploadposts/ads/chatgpt/insights \
  -H 'Authorization: Apikey your-api-key-here' \
  --data-urlencode 'aggregation_level=campaign' \
  --data-urlencode 'time_granularity=daily' \
  --data-urlencode 'since=2026-09-01' \
  --data-urlencode 'until=2026-09-05' \
  --data-urlencode 'fields=impressions' \
  --data-urlencode 'fields=clicks' \
  --data-urlencode 'fields=spend'
```

```json
{
  "success": true,
  "insights": [
    {
      "campaign_id": "cmpn_101",
      "readable_time": "2026-09-01",
      "timezone": "UTC",
      "impressions": 15548,
      "clicks": 312,
      "spend": 42.75
    }
  ],
  "count": 1,
  "has_more": false,
  "first_id": "start=1756684800:end=1756771200:entity_id=cmpn_101",
  "last_id": "start=1756684800:end=1756771200:entity_id=cmpn_101"
}
```

Page forward by sending the previous response's `last_id` as `after`.

---

## Amounts and micros

OpenAI expresses money in **micros** — millionths of your ad account's currency unit. Upload-Post accepts either form:

| You send                     | OpenAI receives          | Meaning                   |
| :--------------------------- | :----------------------- | :------------------------- |
| `"budget": 25`               | `25000000`               | 25.00 lifetime budget     |
| `"budget_micros": 25000000`  | `25000000`               | same                      |
| `"max_bid": 0.06`            | `60000`                  | $60 CPM (per impression)  |
| `"max_bid_micros": 60000`    | `60000`                  | same                      |

All amounts are in the ad account's own currency (`currency_code` on the connect response).

---

## Errors

| Status | Meaning                                                                                          |
| :----- | :------------------------------------------------------------------------------------------------ |
| `400`  | Validation failed, or OpenAI rejected the request. `message` carries the upstream reason.        |
| `401`  | Missing Upload-Post credentials, or the stored OpenAI key was revoked — reconnect the account.   |
| `404`  | No ChatGPT Ads account connected, or the campaign/ad group/ad does not exist.                     |
| `413`  | Creative file larger than 10 MB.                                                                 |
| `429`  | Rate limited (by Upload-Post or by OpenAI). Retry after a short pause.                            |
| `502`  | OpenAI Ads is unreachable or returned an unexpected response.                                     |
| `504`  | OpenAI Ads did not respond in time.                                                              |

```json
{
  "success": false,
  "message": "ChatGPT Ads rejected the stored API key. Issue a new one in Ads Manager (https://ads.openai.com → Settings → API keys) and reconnect the account."
}
```

---

## Related

- [User Profiles](./user-profiles.md) — connect the social accounts you publish with
- [Get Analytics](./get-analytics.md) — organic performance for your published posts
- [OpenAI Ads documentation](https://developers.openai.com/ads) — the upstream API and its policies


---
# Comments (all platforms)
URL: https://docs.upload-post.com/api/comments

# Comments (all platforms)

List, create, delete, and moderate comments on your posts across multiple social networks with a single, consistent API. These endpoints call each platform's native API in real-time using the connection stored for the given profile. Reddit comments currently return HTTP 503 `error_code: "reddit_unavailable"`.

Every operation is one endpoint with a `platform`, never one endpoint per network — the same reason [`/audience`](./audience.md), [`/suggestions`](./suggestions.md) and [`/post-analytics`](./get-analytics.md#get-apiuploadpostspost-analyticsplatform_post_id) are shaped that way. A network that cannot do something yet answers `400` with `error_code: "platform_not_supported"` and names the ones that can.

## Platform support

| Platform  | List | Create | Delete | Replies | Hide / Like / Pin | Notes                                                                                                  |
| :-------- | :--: | :----: | :----: | :-----: | :---------------: | :----------------------------------------------------------------------------------------------------- |
| Instagram |  ✅  |   ✅   |   ✅   |    —    | hide / unhide; enable/disable comments | Creating a comment requires `comment_id`. `like` and `pin` are not supported. |
| Facebook  |  ✅  |   ✅   |   ✅   |    —    | hide / unhide / like / unlike / edit | `pin` returns 400. Create accepts `attachment_url` (image) and `attachment_share_url` (GIF). |
| YouTube   |  ✅  |   ✅   |   ✅   |   ✅    | hide / unhide / hold | `hide` maps to `rejected`, `unhide` to `published`, `hold` to `heldForReview`. `ban_author=true` only with `hide`. |
| LinkedIn  |  ✅  |   ✅   |   ✅   |    —    |         —         | Organization (company page) posts. Use the post **URN** as `post_id`.                                  |
| TikTok    |  ✅  |   ✅   |   ✅   |   ✅    |        ✅         | Needs a **reconnected** TikTok account (the `comments` capability). `post_id` is the video id. See below. |
| X         |  ✅  |   ✅   |   ✅   |    —    |         —         | List uses mentions (`source: "mentions"`). Hide is not available. |
| Threads   |  ✅  |   ✅   |   ❌    |    —    | hide / unhide / approve / ignore | List is partial (`partial: true`). Delete returns `platform_not_supported`. |
| Bluesky   |  ✅  |   ✅   |   ✅   |    —    |         —         | `post_id` is the `at://` URI. |
| Reddit    |  ❌  |   ❌   |   ❌    |    —    |         —         | HTTP 503 `reddit_unavailable`. |

Replies are the same listing narrowed to a parent (`comment_id` on
[List Comments](#list-comments)); hide / like / pin are
[one endpoint with a verb](#hide-like-or-pin-a-comment).

- **TikTok** needs a reconnected account: comment permission is granted at connect time, so only accounts reconnected from [Manage Users](https://app.upload-post.com/manage-users) report `comments` in [`capabilities`](./user-profiles.md#account-capabilities). Older connections still publish, but every comment call returns `400` (`error_code: "tiktok_reconnect_required"`) until reconnect. Check `capabilities` before showing a comment inbox. `post_id` is the **video id** (same as the upload); `post_url` is accepted as that id.
- **YouTube** needs the `youtube.force-ssl` OAuth scope. Accounts connected before that scope existed must reconnect or comment calls fail. `post_id` is the **video ID** (e.g. `dQw4w9WgXcQ`).
- **LinkedIn** `post_id` / `post_url` is the post **URN**, e.g. `urn:li:ugcPost:1234567890`. Same URN for list, create and delete.

---

## List Comments

Retrieve comments on one of your posts. Returns the comments as provided by the target platform.

Add `comment_id` and the same call returns the **replies under that comment**
instead of the post's top-level comments. It is not a different URL: it is the
same question — *show me the comments* — narrowed to a parent.

### Endpoint

```
GET /api/uploadposts/comments
```

### Headers

| Name          | Value                    | Description                     |
| :------------ | :----------------------- | :------------------------------ |
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Query Parameters

| Name       | Type    | Required | Description                                                                                                          |
| :--------- | :------ | :------- | :----------------------------------------------------------------------------------------------------------------- |
| `platform` | String  | No       | One of `instagram`, `facebook`, `youtube`, `linkedin`, `tiktok`, `x`, `threads`, `bluesky`. Defaults to `instagram`. Reddit returns 503 `reddit_unavailable`. |
| `user`     | String  | Yes      | Profile username (as configured in Upload-Post).                                                                    |
| `post_id`  | String  | Yes\*    | Post identifier. YouTube = the video ID; TikTok = the video id; LinkedIn = the post URN (`urn:li:ugcPost:...`); Instagram = numeric media ID. Use `post_id` **or** `post_url`. |
| `post_url` | String  | Yes\*    | Full post URL. Alternative to `post_id`.                                                                            |
| `limit`    | Integer | No       | Maximum comments to return per page.                                                                                |
| `after`    | String  | No       | Pagination cursor returned in the previous response. Pass it back to fetch the next page.                           |
| `comment_id` | String | No      | **TikTok.** Return the replies hanging off this comment instead of the post's top-level comments. `post_id` is still required. |

\* Provide either `post_id` or `post_url` (one is required).

### Example Requests

**Instagram:**

```bash
curl 'https://api.upload-post.com/api/uploadposts/comments?platform=instagram&user=my-profile&post_id=17890455123456789&limit=50' \
  -H 'Authorization: Apikey your-api-key-here'
```

**YouTube (post_id is the video ID):**

```bash
curl 'https://api.upload-post.com/api/uploadposts/comments?platform=youtube&user=my-profile&post_id=dQw4w9WgXcQ' \
  -H 'Authorization: Apikey your-api-key-here'
```

**LinkedIn (post_id is the post URN):**

```bash
curl 'https://api.upload-post.com/api/uploadposts/comments?platform=linkedin&user=my-profile&post_id=urn:li:ugcPost:1234567890' \
  -H 'Authorization: Apikey your-api-key-here'
```

**TikTok (post_id is the video id):**

```bash
curl 'https://api.upload-post.com/api/uploadposts/comments?platform=tiktok&user=my-profile&post_id=7401234567890123456&limit=20' \
  -H 'Authorization: Apikey your-api-key-here'
```

**TikTok — the replies under one comment (add `comment_id`):**

```bash
curl 'https://api.upload-post.com/api/uploadposts/comments?platform=tiktok&user=my-profile&post_id=7401234567890123456&comment_id=7401234567890999888&limit=20' \
  -H 'Authorization: Apikey your-api-key-here'
```

:::caution A comment you just wrote takes ~10 seconds to appear
TikTok indexes a new comment or reply asynchronously. Listing immediately after
[creating one](#create-comment) returns a list **without it** — the call
succeeded, the comment exists, TikTok has simply not indexed it yet. Wait about
10 seconds before re-listing, or render the comment you just created from the
`create` response instead of re-fetching. An empty list right after a write is
not a failure.
:::

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "comments": [
    {
      "id": "17858893269123456",
      "text": "Great post!",
      "timestamp": "2025-06-15T10:30:00+0000",
      "user": {
        "id": "17841400123456789",
        "username": "commenter_user"
      }
    }
  ],
  "pagination": {
    "next_cursor": "QVFIUm9TbGd...",
    "has_next": true
  }
}
```

> The exact comment fields vary by platform (each network returns its own shape). Pagination fields are present when the platform supports cursor-based paging.

### Error Responses

- **400 Bad Request** — missing parameters, invalid post identifier, or an unsupported platform.
- **400 Bad Request** — `error_code: "tiktok_reconnect_required"`: the TikTok connection cannot manage comments. The account owner must reconnect it from [Manage Users](https://app.upload-post.com/manage-users).
- **403 Forbidden** — the connected account lacks the required scope (e.g. YouTube not connected with `youtube.force-ssl`).
- **409 Conflict** — the TikTok token expired and the account must be reconnected (`"reauth_required": true`).
- **500 Internal Server Error**

---

## Create Comment

Post a comment or a reply. Provide exactly one of `comment_id`, `post_id`, or `post_url` to identify the target:

- `comment_id` → reply to that comment.
- `post_id` / `post_url` → top-level comment on that post.

Instagram only supports replies — you **must** send `comment_id`. It cannot create a top-level comment via the API.

On TikTok, `post_id` (the video id) is **always required**, for a top-level comment and for a reply. Adding `comment_id` turns the call into a reply. You can only comment on your own videos.

### Endpoint

```
POST /api/uploadposts/comments/create
```

### Headers

| Name          | Value                    | Description                     |
| :------------ | :----------------------- | :------------------------------ |
| Authorization | Apikey your-api-key-here | Your API key for authentication |
| Content-Type  | application/json         | Request body format             |

### Body Parameters (JSON)

| Name         | Type   | Required | Description                                                                                                          |
| :----------- | :----- | :------- | :----------------------------------------------------------------------------------------------------------------- |
| `platform`   | String | Yes      | One of `instagram`, `facebook`, `youtube`, `linkedin`, `tiktok`, `x`, `threads`, `bluesky`. Reddit returns 503 `reddit_unavailable`. |
| `user`       | String | Yes      | Profile username (as configured in Upload-Post).                                                                    |
| `message`    | String | Yes      | The comment text.                                                                                                   |
| `comment_id` | String | Yes\*    | Reply to this comment. **Required for Instagram.**                                                                  |
| `post_id`    | String | Yes\*    | Top-level comment on this post. LinkedIn = the post URN (`urn:li:ugcPost:...`); YouTube = the video ID; TikTok = the video id, **required even when replying**. |
| `post_url`   | String | Yes\*    | Top-level comment on this post (by URL). Alternative to `post_id`.                                                  |
| `attachment_url` | String | No   | Facebook: image attached to the comment. |
| `attachment_share_url` | String | No | Facebook: GIF attached to the comment. |

\* Provide exactly **one** of `comment_id`, `post_id`, or `post_url` — except on TikTok, where `post_id` is always required and `comment_id` is added to it to reply.

### Example Requests

**Reply to a comment (Instagram):**

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/comments/create \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "instagram",
    "user": "my-profile",
    "comment_id": "17858893269123456",
    "message": "Thanks for your comment!"
  }'
```

**Reply to a TikTok comment (post_id AND comment_id):**

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/comments/create \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "tiktok",
    "user": "my-profile",
    "post_id": "7401234567890123456",
    "comment_id": "7401234567890999888",
    "message": "Thanks! The full guide is in the bio."
  }'
```

**Top-level comment on a LinkedIn post (post_id is the URN):**

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/comments/create \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "linkedin",
    "user": "my-profile",
    "post_id": "urn:li:ugcPost:1234567890",
    "message": "Great update!"
  }'
```

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "id": "17858893269654321",
  "message": "Comment created successfully"
}
```

On TikTok the answer names the platform and returns TikTok's own result object:

```json
{
  "success": true,
  "platform": "tiktok",
  "result": { "comment_id": "7401234567890777111" }
}
```

### Error Responses

- **400 Bad Request** — missing fields, more than one target provided, Instagram without `comment_id`, TikTok without `post_id`, or an unsupported platform.
- **400 Bad Request** — `error_code: "tiktok_reconnect_required"`: the TikTok connection cannot manage comments; reconnect the account.
- **403 Forbidden** — the connected account lacks the required scope.
- **409 Conflict** — the TikTok token expired (`"reauth_required": true`).
- **500 Internal Server Error**

---

## Delete Comment

Delete a comment you own (or that is on your post). Accepts `DELETE` or `POST`.

### Endpoint

```
DELETE /api/uploadposts/comments/delete
```

> This endpoint also accepts `POST` with the same body, for clients that cannot send a body with `DELETE`.

### Headers

| Name          | Value                    | Description                     |
| :------------ | :----------------------- | :------------------------------ |
| Authorization | Apikey your-api-key-here | Your API key for authentication |
| Content-Type  | application/json         | Request body format             |

### Body Parameters (JSON)

| Name         | Type   | Required | Description                                                                                       |
| :----------- | :----- | :------- | :----------------------------------------------------------------------------------------------- |
| `platform`   | String | Yes      | One of `instagram`, `facebook`, `youtube`, `linkedin`, `tiktok`.                                 |
| `user`       | String | Yes      | Profile username (as configured in Upload-Post).                                                 |
| `comment_id` | String | Yes      | The ID of the comment to delete.                                                                 |
| `post_id`    | String | Yes\*    | **Required for LinkedIn only** — the post URN (`urn:li:ugcPost:...`) the comment belongs to.     |

\* `post_id` is required for LinkedIn; ignored for the other platforms. TikTok deletes a comment from its `comment_id` alone — and only a comment written by the connected account itself.

### Example Requests

**Delete a Facebook comment:**

```bash
curl -X DELETE https://api.upload-post.com/api/uploadposts/comments/delete \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "facebook",
    "user": "my-profile",
    "comment_id": "17858893269123456"
  }'
```

**Delete a LinkedIn comment (post_id required):**

```bash
curl -X DELETE https://api.upload-post.com/api/uploadposts/comments/delete \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "linkedin",
    "user": "my-profile",
    "comment_id": "urn:li:comment:(urn:li:ugcPost:1234567890,9876543210)",
    "post_id": "urn:li:ugcPost:1234567890"
  }'
```

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "message": "Comment deleted successfully"
}
```

### Error Responses

- **400 Bad Request** — missing fields, LinkedIn without `post_id`, or an unsupported platform.
- **400 Bad Request** — `error_code: "tiktok_reconnect_required"`: the TikTok connection cannot manage comments; reconnect the account.
- **403 Forbidden** — the connected account lacks the required scope, or you do not own the comment.
- **409 Conflict** — the TikTok token expired (`"reauth_required": true`).
- **500 Internal Server Error**

---

## Hide, Like or Pin a Comment {#hide-like-or-pin-a-comment}

Moderate a comment on one of your own posts. One endpoint, because every action
here is a verb that carries its own inverse: `hide` / `unhide`, `like` /
`unlike`, `pin` / `unpin`. You send the verb you want, never a boolean, so
replaying a request can never flip a comment back to where it started.

### Endpoint

```
POST /api/uploadposts/comments/action
```

### Headers

| Name          | Value                    | Description                     |
| :------------ | :----------------------- | :------------------------------ |
| Authorization | Apikey your-api-key-here | Your API key for authentication |
| Content-Type  | application/json         | Request body format             |

### Body Parameters (JSON)

| Name         | Type   | Required | Description                                                                                       |
| :----------- | :----- | :------- | :------------------------------------------------------------------------------------------------ |
| `platform`   | String | Yes      | `tiktok`, `facebook`, `instagram`, `youtube`, `threads`. Others answer `400` `platform_not_supported`. |
| `user`       | String | Yes      | Profile username (as configured in Upload-Post).                                                  |
| `comment_id` | String | Conditional | The comment to act on. Required except Instagram `enable_comments` / `disable_comments`. |
| `action`     | String | Yes      | TikTok: `hide`, `unhide`, `like`, `unlike`, `pin`, `unpin`. Facebook: `hide`, `unhide`, `like`, `unlike`, `edit` (needs `message`); `pin` is 400. Instagram: `hide`, `unhide` (`comment_id`); `enable_comments`, `disable_comments` (`post_id`). YouTube: `hide`, `unhide`, `hold` (`ban_author=true` only with `hide`). Threads: `hide`, `unhide`, `approve`, `ignore`. |
| `post_id`    | String | Conditional | Required for TikTok `hide` / `unhide` / `pin` / `unpin`, Instagram comment-enable/disable, and YouTube moderation. |
| `message`    | String | Conditional | Required for Facebook `action=edit`. |
| `ban_author` | Boolean | No     | YouTube: only with `action=hide`. |

### Example Requests

**Hide a comment:**

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/comments/action \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "tiktok",
    "user": "my-profile",
    "comment_id": "7401234567890999888",
    "action": "hide",
    "post_id": "7401234567890123456"
  }'
```

**Like a comment (no `post_id`):**

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/comments/action \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "tiktok",
    "user": "my-profile",
    "comment_id": "7401234567890999888",
    "action": "like"
  }'
```

**Pin a comment to the top of the post:**

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/comments/action \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "tiktok",
    "user": "my-profile",
    "comment_id": "7401234567890777111",
    "action": "pin",
    "post_id": "7401234567890123456"
  }'
```

To undo any of them, send the inverse verb with the same body: `unhide`,
`unlike`, `unpin`.

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "platform": "tiktok",
  "action": "hide",
  "comment_id": "7401234567890999888"
}
```

The echoed `action` is the confirmation to branch on.

### Error Responses

- **400 Bad Request** — a platform that cannot moderate comments yet:

```json
{
  "success": false,
  "error": "'instagram' cannot act on comments yet. Supported: tiktok.",
  "error_code": "platform_not_supported"
}
```

- **400 Bad Request** — an `action` outside the six verbs:

```json
{ "success": false, "error": "action must be one of: hide, like, pin, unhide, unlike, unpin" }
```

- **400 Bad Request** — `post_id` omitted on `hide` / `unhide` / `pin` / `unpin`:

```json
{ "success": false, "error": "post_id is required to pin a comment" }
```

- **400 Bad Request** — `error_code: "tiktok_reconnect_required"`: the TikTok
  connection cannot manage comments; reconnect the account.
- **404 Not Found** — profile not found.
- **409 Conflict** — the TikTok token expired (`"reauth_required": true`).
- **502 Bad Gateway** — the platform rejected the operation; its message comes
  back in `error`.

### Additional Notes

- Only comments on **your own** posts can be moderated. Anything else is refused
  upstream and comes back as a `502` carrying the platform's message.
- Pinning is exclusive: pinning a second comment moves the pin, it does not add
  one.
- A comment you hide stays visible to whoever wrote it — that is TikTok's own
  behaviour, not something this API changes.

---

## Related

- [Account capabilities](./user-profiles.md#account-capabilities) — check `comments` before offering a TikTok inbox.
- [Instagram private & public replies](./instagram-comments.md) — Instagram-specific private-reply DMs and public replies.
- [Direct Messages](./instagram-dms.md) — send DMs to a commenter using their user ID.


---
# Connect API (Build Your Own Connect Page)
URL: https://docs.upload-post.com/api/connect-api

# Connect API — Build Your Own Connect Page

The Connect API lets you build the "connect your social accounts" experience **inside your own product, on your own domain, with your own design** — instead of sending end users to the hosted `access_url` page described in the [White-label Integration Guide](../guides/user-profile-integration.md).

Your page requests a platform authorize URL from Upload-Post, redirects the end user to the social network's consent screen, and Upload-Post handles the OAuth exchange and token storage. Whether the connection succeeds, fails, or the user cancels, the user is sent back to the URL you choose with a machine-readable outcome.

**When to use which:**

| | Hosted page (`access_url`) | Connect API (this page) |
|---|---|---|
| Setup effort | None — one API call | You build the UI |
| Branding | Logo, title, colors, language | 100% yours — it's your page |
| Domain the user sees | `app.upload-post.com` | Yours (except the OAuth hops) |

> **Note:** During the OAuth flow the user always visits the social network's own consent screen, and the network redirects briefly through `app.upload-post.com` (the OAuth callback registered with each platform) before returning to your `redirect_url`. This hop lasts milliseconds and is required by the platforms' OAuth policies — it applies to every provider in the industry.

## How it works

```
Your backend                Your connect page             Upload-Post              Social network
     │                            │                            │                        │
     │ 1. generate-jwt (API key)  │                            │                        │
     │───────────────────────────>│                            │                        │
     │    profile JWT             │                            │                        │
     │                            │ 2. POST /oauth/<platform>/start (profile JWT)      │
     │                            │───────────────────────────>│                        │
     │                            │    authorize_url + state   │                        │
     │                            │ 3. redirect user ──────────────────────────────────>│
     │                            │                            │  4. user authorizes    │
     │                            │                            │<── code + state ───────│
     │                            │                            │  5. token exchange,    │
     │                            │                            │     account stored     │
     │                            │<── 6. redirect to your redirect_url ────────────────│
     │                            │    ?connect_status=success|error|cancelled           │
```

1. Your **backend** calls [`generate-jwt`](./user-profiles.md) with your API key to obtain a profile token for the end user's profile.
2. Your **connect page** calls the start endpoint below with that profile token and receives the `authorize_url`.
3. You redirect the user to `authorize_url` (the social network's consent screen).
4. After consent, the network redirects to Upload-Post's registered callback, which completes the exchange — authenticated by the single-use `state`, so the user's browser needs no session with Upload-Post.
5. The user lands back on your `redirect_url` with `?connect_status=success&platform=<platform>` — or, when they cancelled or something failed, with `connect_status=cancelled` / `connect_status=error` and a stable `error_code` (see [Handling the return](#handling-the-return)).

## Start endpoint

```
POST /api/uploadposts/oauth/{platform}/start
```

**Supported platforms:** `tiktok`, `instagram`, `facebook`, `linkedin`, `youtube`, `x` (alias: `twitter`), `threads`, `pinterest`, `google-business`, `snapchat`

Reddit OAuth (`reddit`) currently returns **HTTP 503** `error_code: "reddit_unavailable"`.

### Authentication

Any of:

- **Profile JWT** (recommended for browser calls): `Authorization: Bearer PROFILE_JWT` — the token from [`generate-jwt`](./user-profiles.md). The profile is taken from the token.
- **API key** (server-to-server): `Authorization: Apikey YOUR_API_KEY` — pass the profile in the body.

Never expose your API key in a browser; use the profile JWT there.

### Request body

| Field | Type | Required | Description |
|---|---|---|---|
| `profile` | string | Only with API key auth | Profile username the connected account will be linked to. Ignored when a profile JWT is used. |
| `redirect_url` | string | No | Absolute `http(s)` URL (max 2000 chars) to send the end user back to once the attempt is over — on success, on failure and on cancel. `connect_status`, `platform` and (on failure) `error_code` are appended as query parameters. |

### Response

```json
{
  "success": true,
  "platform": "instagram",
  "authorize_url": "https://www.instagram.com/oauth/authorize?client_id=...&state=...",
  "state": "e34zUEyZjWy6EFeI7IzGSEiUfY8O4K4_",
  "expires_in": 900
}
```

Redirect the end user to `authorize_url` **within 15 minutes** (`expires_in`) — the underlying `state` is single-use and expires after that window. Mint a fresh one per connection attempt.

### Example — browser (profile JWT)

```javascript
const resp = await fetch(
  'https://api.upload-post.com/api/uploadposts/oauth/instagram/start',
  {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${profileJwt}`,
    },
    body: JSON.stringify({
      redirect_url: 'https://yourapp.com/social/connected',
    }),
  }
);
const { authorize_url } = await resp.json();
window.location.href = authorize_url;
```

### Example — server (API key)

```bash
curl -X POST "https://api.upload-post.com/api/uploadposts/oauth/tiktok/start" \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "profile": "end-user-123",
    "redirect_url": "https://yourapp.com/social/connected"
  }'
```

### Errors

| Status | Meaning |
|---|---|
| `400` | Missing `profile` (API key auth) or invalid `redirect_url` |
| `401` | Missing or invalid authentication |
| `404` | Unknown platform, or profile not found for this account (`error_code: PROFILE_NOT_FOUND`) |
| `500` | Platform client not configured on the server |

## Handling the return

Every attempt ends on your `redirect_url` (when you passed one) with the same `redirect_url` for all outcomes — there is no separate failure URL to configure. Upload-Post appends these query parameters:

| Parameter | Values | Description |
|---|---|---|
| `connect_status` | `success`, `cancelled`, `error` | Outcome of the attempt. |
| `platform` | the platform you started | Same public names as the start endpoint (`tiktok`, `instagram`, `x`, …). |
| `error_code` | see below | Only on `cancelled` / `error`. Stable and machine-readable — key your UI on this, never on the description. |
| `error_description` | free text, ≤ 200 chars | Only when the social network returned one. Not guaranteed, not localized; safe to display but not to parse. |

**Success:**

```
https://yourapp.com/social/connected?connect_status=success&platform=instagram
```

To confirm the connection server-side (recommended), call [`GET /api/uploadposts/users`](./user-profiles.md) and check the profile's `social_accounts`.

**The user cancelled on the consent screen:**

```
https://yourapp.com/social/connected?connect_status=cancelled&platform=facebook&error_code=ACCESS_DENIED
```

**The connection failed:**

```
https://yourapp.com/social/connected?connect_status=error&platform=linkedin&error_code=CONNECTION_FAILED
```

### Error codes

| `error_code` | `connect_status` | Meaning | Suggested action |
|---|---|---|---|
| `ACCESS_DENIED` | `cancelled` | The end user declined or closed the network's consent screen. | Offer a retry. |
| `PROVIDER_ERROR` | `error` | The social network returned an OAuth error other than a cancel (outage, app misconfiguration on their side). `error_description` usually carries their message. | Offer a retry; escalate if it persists. |
| `ACCOUNT_ALREADY_LINKED` | `error` | That social account is already connected to a different Upload-Post user. Each social account can be linked to one user at a time. | Tell the user to connect a different account, or contact support to move it. |
| `INVALID_STATE` | `error` | The `state` was missing, expired (15 min), already used, or did not match. | Mint a fresh start and retry. |
| `CONNECTION_FAILED` | `error` | The token exchange or profile fetch failed on the Upload-Post side. | Offer a retry; contact support if it persists. |

Each attempt maps to exactly one return: the `state` minted at start is single-use, so a retry always goes through a fresh `POST /oauth/{platform}/start`.

Without a `redirect_url`, a failed or cancelled attempt shows the Upload-Post error screen with a human-readable message and the user can retry from your page with a freshly minted start.

### Example — reading the outcome on your page

```javascript
const params = new URLSearchParams(window.location.search);
const status = params.get('connect_status');   // success | cancelled | error
const platform = params.get('platform');

if (status === 'success') {
  showConnected(platform);
} else if (params.get('error_code') === 'ACCESS_DENIED') {
  showRetry(platform, 'You closed the authorization window.');
} else {
  showRetry(platform, `Could not connect ${platform} (${params.get('error_code')}).`);
}
```

## Security model

- The `state` in the authorize URL is a 192-bit random value stored server-side, **single-use** and valid for **15 minutes**. It is what authenticates the OAuth callback, so the end user's browser never needs an Upload-Post session.
- The `state` is bound to the platform, profile and account that minted it — it cannot be replayed, reused across platforms, or combined with another account's session.
- OAuth secrets (PKCE verifiers for TikTok and X) never leave the server; only the S256 challenge appears in the authorize URL.
- `redirect_url` is validated to be an absolute `http(s)` URL, is never followed server-side, and is only ever reached by the end user's own browser — after a successful connection, or after a failed or cancelled one together with `connect_status` and `error_code`.

## Notes per platform

- **`youtube`**: profiles configured with [custom YouTube credentials](./user-profiles.md) authorize against their own Google client automatically.
- **`x` / `twitter`**: both names are accepted; responses always report `platform: "x"`.
- **`snapchat`**: connection is limited to basic profile scopes (posting requires Snapchat Public Profile API approval).
- **`instagram`**: the end user's Instagram must be a Business or Creator account, same as the hosted flow.


---
# Current User API
URL: https://docs.upload-post.com/api/current-user

# Current User API

Verify the validity of your API key and retrieve basic account information.

## Authentication

```
Authorization: Apikey YOUR_API_KEY
```

---

## Get Current User

Validates your API key and returns the associated email and subscription plan.

### Endpoint

```
GET /api/uploadposts/me
```

### Headers

| Name          | Required | Description                |
|---------------|----------|----------------------------|
| Authorization | Yes      | `Apikey YOUR_API_KEY`      |

### Example Request (curl)

```bash
curl -X GET https://api.upload-post.com/api/uploadposts/me \
  -H "Authorization: Apikey YOUR_API_KEY"
```

### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Token is valid",
  "email": "user@example.com",
  "plan": "Professional",
  "preferences": {
    "weekStartDay": 1
  }
}
```

**Response Fields:**

| Field   | Type    | Description                                                        |
|---------|---------|--------------------------------------------------------------------|
| success | Boolean | Always `true` for successful requests                              |
| message | String  | Confirmation message                                               |
| email   | String  | The email address associated with the authenticated account        |
| plan    | String  | Current subscription plan (e.g., `Basic`, `Professional`, `Business`, `Default`) |
| preferences | Object | User preferences. See [Preferences](#preferences) below.      |

### Error Responses

**401 Unauthorized** - Invalid or missing authentication

```json
{
  "success": false,
  "message": "Invalid or expired token"
}
```

**500 Internal Server Error** - Server-side error

```json
{
  "success": false,
  "message": "Error description"
}
```

---

## Preferences

Manage user-level preferences via the preferences endpoint.

### Get Preferences

```
GET /api/uploadposts/users/preferences
```

**Response (200 OK):**

```json
{
  "success": true,
  "preferences": {
    "weekStartDay": 1
  }
}
```

### Update Preferences

```
POST /api/uploadposts/users/preferences
```

**Body (JSON):**

| Field        | Type    | Description                                     |
|--------------|---------|-------------------------------------------------|
| weekStartDay | Integer | Calendar week start day. `0` = Sunday, `1` = Monday. |

**Example:**

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/preferences \
  -H 'Authorization: Apikey YOUR_API_KEY' \
  -H 'Content-Type: application/json' \
  -d '{"weekStartDay": 1}'
```

**Response (200 OK):**

```json
{
  "success": true,
  "preferences": {
    "weekStartDay": 1
  }
}
```

**Error (400 Bad Request):**

```json
{
  "success": false,
  "message": "weekStartDay must be 0 (Sunday) or 1 (Monday)"
}
```

---

## Use Cases

- **Token Validation**: Verify that your API key or JWT is still valid before making other API calls
- **Plan Check**: Determine the current subscription plan to understand available features and limits
- **Account Verification**: Confirm which account is associated with your credentials


---
# Edit a Published Post
URL: https://docs.upload-post.com/api/edit-post

# Edit a Published Post

Update caption or metadata on a post that is already live. Fields you omit are left unchanged.

## Platform support

| Platform        | Edit | Notes |
| :-------------- | :--: | :---- |
| Facebook        |  ✅  | `message`, pin, scheduled time. Only posts published through Upload-Post. |
| LinkedIn        |  ✅  | `commentary` (or `text`). |
| X               |  ✅  | Premium, within 30 minutes, fewer than 5 edits. The post ID **changes**. |
| YouTube         |  ✅  | title, description, tags, privacy, category, publishAt. |
| Google Business |  ✅  | localPosts.patch. |
| Instagram       |  ❌  | No edit API for published media. |
| TikTok          |  ❌  | |
| Threads         |  ❌  | |

Credential channels that implement edit (Discord, Telegram, Mastodon, Hashnode, Whop, …) are also accepted.

## Endpoint

```
POST /api/uploadposts/posts/edit
```

Alias: `POST /api/uploadposts/posts/action` with `"action": "edit"` (LinkedIn).

## Headers

| Name          | Value                    | Description                     |
| :------------ | :----------------------- | :------------------------------ |
| Authorization | Apikey your-api-key-here | Your API key for authentication |
| Content-Type  | application/json         | Request body format             |

## Body Parameters (JSON)

| Name | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `platform` | String | Yes | `facebook`, `linkedin`, `x`, `youtube`, `google_business`, plus credential channels that support edit. |
| `user` | String | Yes | Profile username. |
| `post_id` | String | Yes | Platform post ID (YouTube also accepts `video_id`). |
| `message` / `text` / `commentary` | String | No | New caption. Facebook: `message`. X: `text`. LinkedIn: `commentary`. |
| `is_pinned` | Boolean | No | Facebook: pin/unpin. |
| `scheduled_publish_time` | String | No | Facebook. Unix timestamp in seconds. |
| `facebook_page_id` | String | No | Facebook Page if not the profile default. |
| `linkedin_visibility` | String | No | LinkedIn visibility on edit. |
| `linkedin_disable_reshare` | Boolean | No | LinkedIn. |
| `title` / `description` / `tags` / `privacyStatus` / `categoryId` / `publishAt` | — | No | YouTube. Aliases with `youtube_*` prefix are accepted. |

## Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/posts/edit \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "facebook",
    "user": "my-profile",
    "post_id": "1234567890",
    "message": "Updated caption"
  }'
```

X:

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/posts/edit \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "x",
    "user": "my-profile",
    "post_id": "1234567890123456789",
    "text": "Corrected tweet"
  }'
```

## Successful Response (`200 OK`)

Facebook:

```json
{
  "success": true,
  "platform": "facebook",
  "post_id": "1234567890",
  "updated": ["message"]
}
```

X returns a **new** `post_id`. Use that id from now on; the previous id stays in `edit_history_post_ids`.

```json
{
  "success": true,
  "platform": "x",
  "post_id": "9876543210987654321",
  "previous_post_id": "1234567890123456789",
  "edit_history_post_ids": ["1234567890123456789", "9876543210987654321"],
  "url": "https://x.com/handle/status/9876543210987654321",
  "message": "X assigns a new post id to every edit. Use the returned post_id from now on; the previous id stays in edit_history_post_ids."
}
```

LinkedIn:

```json
{
  "success": true,
  "message": "LinkedIn post updated.",
  "post_id": "urn:li:share:123",
  "url": "https://www.linkedin.com/feed/update/urn:li:share:123/"
}
```

## Error Responses

- **400 Bad Request** — missing fields, nothing to update, or an unsupported platform (e.g. `instagram`, `tiktok`).
- **403 Forbidden** — the connected account is not authorized to edit the post.
- **404 Not Found** — no post found for the given `post_id`.
- **500 Internal Server Error**

## Related

- [Unpublish](./unpublish-post.md)
- [Repost](./repost.md)
- [Retry a failed upload](./retry-post.md)


---
# Pin a Facebook Page to a Profile
URL: https://docs.upload-post.com/api/facebook-page

Connecting Facebook links a Meta **account**, which may manage several Pages. When a
profile should always publish to one specific Page — the usual case when each of your
customers or brands has its own profile — pin that Page to the profile once. From then
on every upload from that profile (video, photos, text, scheduled posts and retries)
goes to the pinned Page and you can omit `facebook_page_id`.

The same selection is available in the dashboard (**User Management → Facebook
card**) and in the white-label [connect page](./connect-api.md), so end users can
pick their Page themselves.

:::info Precedence
A pinned Page **takes precedence** over the `facebook_page_id` parameter of the
upload endpoints. To post to a different Page from the same profile, clear the pin
first (`DELETE`) or use another profile. The daily Facebook cap (25 posts per rolling
24 hours) is applied **per Page**, so each Page has its own allowance no matter how
many profiles or requests target it.
:::

- **Authentication:** any of
  - `Authorization: Apikey <YOUR_API_KEY>` (API key)
  - `Authorization: Bearer <user JWT>` (dashboard session)
  - the profile-scoped JWT issued for the connect page (see [User Profiles API](./user-profiles.md))

### **Get available Pages and the current pin**

- **Method:** `GET`
- **Endpoint:** `/api/uploadposts/users/facebook-page`

| Parameter          | Type   | Description                                  | Required |
| :----------------- | :----- | :------------------------------------------- | :------- |
| `profile_username` | string | The profile's `username` (query parameter). | Yes      |

- **Successful Response (`200 OK`)**

```json
{
  "success": true,
  "pages": [
    { "id": "109876543210987", "name": "My Business Page", "picture": "https://..." },
    { "id": "208765432109876", "name": "Travel Blog", "picture": "https://..." }
  ],
  "selected_page_id": "109876543210987",
  "selected_page_name": "My Business Page"
}
```

`selected_page_id` / `selected_page_name` are `null` when no Page is pinned. The
same two fields are returned for each profile by [User Profiles API](./user-profiles.md)
and by `validate-jwt`.

### **Pin a Page**

- **Method:** `POST`
- **Endpoint:** `/api/uploadposts/users/facebook-page`
- **Body (JSON):**

| Field              | Type   | Description                                                                 | Required |
| :----------------- | :----- | :-------------------------------------------------------------------------- | :------- |
| `profile_username` | string | The profile's `username`.                                                   | Yes      |
| `facebook_page_id` | string | Page `id` from the `pages` list. Must belong to the account connected to this profile. | Yes |

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/facebook-page \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"profile_username": "acme", "facebook_page_id": "109876543210987"}'
```

- **Successful Response (`200 OK`)**

```json
{
  "success": true,
  "facebook_page_id": "109876543210987",
  "facebook_page_name": "My Business Page"
}
```

The Page name is taken from Facebook, never from the request. A `facebook_page_id`
that the connected account cannot manage returns `400`.

### **Clear the pin**

- **Method:** `DELETE`
- **Endpoint:** `/api/uploadposts/users/facebook-page`
- **Body (JSON):** `{"profile_username": "acme"}`

After clearing, uploads fall back to the default behaviour: the `facebook_page_id`
parameter, or auto-selection when the account manages exactly one Page.

### **Error Responses**

| Status | Meaning                                                                      |
| :----- | :--------------------------------------------------------------------------- |
| `400`  | Missing `profile_username` / `facebook_page_id`, profile has no Facebook connected, or the Page is not accessible to that account. |
| `404`  | Profile not found.                                                            |
| `502`  | Facebook did not return the Page list (token expired — reconnect Facebook).  |


---
# FFmpeg Editor API
URL: https://docs.upload-post.com/api/ffmpeg-editor

# FFmpeg Editor API

Process and transform media using your own FFmpeg command safely on our infrastructure. Submit a job with your media and a command template, then poll the job until it finishes and download the result.

### Endpoint

```
POST /api/uploadposts/ffmpeg/jobs/upload
```

### Headers

| Name          | Value                    | Description                      |
|---------------|--------------------------|----------------------------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication. |

### Parameters

| Name              | Type          | Required | Description |
|-------------------|---------------|----------|-------------|
| file              | File (binary) | Yes      | Media file to process. |
| full_command      | String        | Yes      | FFmpeg command template that MUST use `{input}` and `{output}` placeholders. Example: `ffmpeg -y -i {input} -c:v libx264 -crf 23 {output}` |
| output_extension  | String        | Yes      | Desired output file extension (e.g., `mp4`, `wav`, `mp3`, `mov`, `webm`). |

> Note: If the duration of the input media cannot be detected, the system assumes 60 seconds for quota calculation.

### Command Template Rules

For security and reliability, only safe FFmpeg commands are accepted.

 - Use placeholders: `{input}` (or indexed `{input0}`, `{input1}`, …) for input files and `{output}` for the output file; do not hardcode filenames. The first input can be referenced as `{input}` or `{input0}`.
- Allowed pattern starts with `ffmpeg` and may include typical flags (e.g., `-y`, `-i`, `-c:v`, `-c:a`, `-r`, `-b:v`, filters, etc.).
- Blocked characters/constructs to prevent command injection: `;`, `|`, `&`, `$`, `\``, `$(`, and destructive commands like `rm`/`rmdir`.
- Newlines and carriage returns in the command string are automatically replaced with spaces (not blocked), so pasting multi-line commands works. To render multi-line text in the `drawtext` filter, use the literal escape `\n` (backslash + n) — in JSON, send `\\n`.
- If validation fails, the API returns 400 Bad Request with a helpful message.

### Responses

- 202 Accepted (job created)

```json
{
  "success": true,
  "job_id": "a97bbb5a-139b-46ca-b893-6e8d303d5934",
  "status": "PENDING"
}
```

### Check Job Status

Poll the job until it finishes.

```
GET /api/uploadposts/ffmpeg/jobs/{job_id}
```

Example response:

```json
{
  "job_id": "a97bbb5a-139b-46ca-b893-6e8d303d5934",
  "status": "FINISHED",
  "duration_seconds": 120,
  "output_extension": "mp4"
}
```

Statuses: `PENDING`, `PROCESSING`, `FINISHED`, `ERROR`.

### Download Result

When status is `FINISHED`, download the processed file.

```
GET /api/uploadposts/ffmpeg/jobs/{job_id}/download
```

Response headers include the appropriate `Content-Type` and a `Content-Disposition` attachment filename (e.g., `output.mp4`, `output.wav`). The response body is the binary media.

### Example: Convert to MP4 (H.264)

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'file=@/path/to/input.mov' \
  -F 'full_command=ffmpeg -y -i {input} -c:v libx264 -preset medium -crf 23 -c:a aac -b:a 128k {output}' \
  -F 'output_extension=mp4' \
  -X POST https://api.upload-post.com/api/uploadposts/ffmpeg/jobs/upload
```

### Example: Extract Audio to WAV

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'file=@/path/to/input.mp4' \
  -F 'full_command=ffmpeg -y -i {input} -vn -acodec pcm_s16le -ar 44100 -ac 2 {output}' \
  -F 'output_extension=wav' \
  -X POST https://api.upload-post.com/api/uploadposts/ffmpeg/jobs/upload
```

## Concatenate/Merge multiple videos (NEW)

The API now supports multiple input files for operations like concatenation. You can use placeholders `{input0}`, `{input1}`, `{input2}`, etc., in `full_command`.

### Option A: Send multiple URLs (JSON endpoint)

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/ffmpeg/jobs/upload \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Apikey your-api-key-here' \
  -d '{
    "files": [
      "https://tu-cdn.com/video1.mp4",
      "https://tu-cdn.com/video2.mp4",
      "https://tu-cdn.com/video3.mp4"
    ],
    "full_command": "ffmpeg -y -hide_banner -i {input0} -i {input1} -i {input2} -filter_complex \"[0:v][0:a][1:v][1:a][2:v][2:a]concat=n=3:v=1:a=1[outv][outa]\" -map \"[outv]\" -map \"[outa]\" -c:v h264_nvenc -preset p5 -cq 23 -c:a aac -b:a 128k {output}",
    "output_extension": "mp4",
    "publish": true
  }'
```

### Option B: Upload multiple files (multipart/form-data)

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/ffmpeg/jobs/upload \
  -H 'Authorization: Apikey your-api-key-here' \
  -F "file=@/ruta/video1.mp4" \
  -F "file1=@/ruta/video2.mp4" \
  -F "file2=@/ruta/video3.mp4" \
  -F 'full_command=ffmpeg -y -hide_banner -i {input0} -i {input1} -i {input2} -filter_complex "[0:v][0:a][1:v][1:a][2:v][2:a]concat=n=3:v=1:a=1[outv][outa]" -map "[outv]" -map "[outa]" -c:v h264_nvenc -preset p5 -cq 23 -c:a aac -b:a 128k {output}' \
  -F "output_extension=mp4" \
  -F "publish=true"
```

### Concatenation examples

**Simple concatenation (2 videos):**

```json
{
  "files": ["https://cdn.com/part1.mp4", "https://cdn.com/part2.mp4"],
  "full_command": "ffmpeg -y -i {input0} -i {input1} -filter_complex \"[0:v][0:a][1:v][1:a]concat=n=2:v=1:a=1[v][a]\" -map \"[v]\" -map \"[a]\" -c:v h264_nvenc -cq 23 -c:a aac {output}",
  "output_extension": "mp4"
}
```

**Concatenation with re-encoding (multiple videos):**

```json
{
  "files": [
    "https://cdn.com/intro.mp4",
    "https://cdn.com/contenido.mp4",
    "https://cdn.com/outro.mp4"
  ],
  "full_command": "ffmpeg -y -hwaccel cuda -i {input0} -i {input1} -i {input2} -filter_complex \"[0:v][0:a][1:v][1:a][2:v][2:a]concat=n=3:v=1:a=1[outv][outa]\" -map \"[outv]\" -map \"[outa]\" -c:v h264_nvenc -preset p5 -rc vbr -cq 23 -b:v 4M -c:a aac -b:a 128k {output}",
  "output_extension": "mp4"
}
```

**Using the concat demuxer (no re-encoding — faster but requires identical formats):**

First create a text file with the list of videos, upload it as `file` and the videos as `file1`, `file2`, etc.:

```bash
# Create list (concat-list.txt)
echo "file '/work/.../in-src-0'" > concat-list.txt
echo "file '/work/.../in-src-1'" >> concat-list.txt
echo "file '/work/.../in-src-2'" >> concat-list.txt

# Note: For this method you'll need to adjust paths in the command or use the filter_complex method above
```

`{input}` still points to the first file. Extra files are `{input0}`, `{input1}`, `{input2}`, … The presets (`h264_social`, `hevc_social`, `copy_mux`) and `ffmpeg_args` only accept one file; multiple inputs need `full_command`.

### Example: Draw Multi-Line Text on Video

Use the `drawtext` filter with `\n` for line breaks. In JSON, escape as `\\n`:

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'file=@/path/to/input.mp4' \
  -F 'full_command=ffmpeg -y -i {input} -vf "drawtext=text='"'"'Line 1\nLine 2\nLine 3'"'"':fontsize=24:fontcolor=white:x=10:y=10" {output}' \
  -F 'output_extension=mp4' \
  -X POST https://api.upload-post.com/api/uploadposts/ffmpeg/jobs/upload
```

JSON body example:

```json
{
  "full_command": "ffmpeg -y -i {input} -vf \"drawtext=text='Hello World\\nSecond Line':fontsize=28:fontcolor=white:x=(w-text_w)/2:y=(h-text_h)/2\" {output}",
  "output_extension": "mp4",
  "input_url": "https://example.com/video.mp4"
}
```

### Quotas by Plan (minutes of media/month)

| Plan          | Minutes/Month |
|---------------|----------------|
| free          | 30             |
| basic         | 300            |
| professional  | 1000           |
| advanced      | 3000           |
| business      | 10000          |

Resets on the 1st of each month at 00:00 UTC.

### Check Your FFmpeg Consumption

To check your current FFmpeg usage and remaining quota, use:

```
GET /api/uploadposts/ffmpeg/consumption
```

Example response:

```json
{
  "success": true,
  "consumption": {
    "used_minutes": 45.5,
    "remaining_minutes": 254.5,
    "quota_minutes": 300,
    "usage_percentage": 15.2,
    "total_requests": 12,
    "current_month": "2024-01",
    "plan": "basic"
  },
  "history": [
    {
      "duration_seconds": 180,
      "duration_minutes": 3.0,
      "timestamp": "2024-01-15T10:30:00.000Z",
      "month": "2024-01",
      "type": "video_processing"
    }
  ],
  "quota_info": {
    "plan": "basic",
    "quota_minutes": 300,
    "reset_day": 1,
    "next_reset": "2024-02-01"
  }
}
```

You can also view your FFmpeg usage in the [API Keys page](https://app.upload-post.com/api-keys) or your [Profile page](https://app.upload-post.com/profile).

### Errors

- 400 Bad Request: Invalid or unsafe FFmpeg command, missing parameters.
- 401 Unauthorized: Invalid or expired API key.
- 404 Not Found: Job not found.
- 429 Too Many Requests: Monthly quota exceeded (response includes current usage when applicable).
- 500 Internal Server Error: Processing error.

### Notes

- Jobs are asynchronous; always poll the job status before attempting to download the output.
- Quota checks use detected media duration to ensure fair usage across plans.


---
# Analytics API
URL: https://docs.upload-post.com/api/get-analytics

:::tip The other half of the question
This page answers *how did my posts do*. Two sibling endpoints answer the rest,
and both are one endpoint with a `platform` — never one per network:
[Audience Insights](./audience.md) (demographics, the hours followers are
online, daily follower gains and losses, profile actions, category benchmark)
and [Suggestions](./suggestions.md) (hashtags and the terms people search).
On TikTok both need the `profile_analytics`
[capability](./user-profiles.md#account-capabilities); `type=keywords` on
Suggestions additionally needs a reconnected account.
:::

### **GET /api/analytics/profile_username**

Retrieves analytics data for a specified user profile across one or more social media platforms.

---

**Method:** `GET`

**Endpoint URL:** `https://api.upload-post.com/api/analytics/profile_username`

**Description:**

This endpoint provides key analytics metrics for a given social media profile associated with a user's account. It allows fetching data for multiple platforms in a single request. The system is designed to be extensible, with support for more platforms planned for the future.

**Authentication:**

A valid JSON Web Token (JWT) is required for authentication. The token must be included in the `Authorization` header as a Apikey token.

`Authorization: Apikey <YOUR_JWT_TOKEN>`

**Parameters:**

| Parameter          | Type   | Location      | Required | Description                                                                                             |
| ------------------ | ------ | ------------- | -------- | ------------------------------------------------------------------------------------------------------- |
| `profile_username` | string | Path          | Yes      | The unique username of the profile for which you want to retrieve analytics.                            |
| `platforms`        | string | Query         | Yes      | A comma-separated list of platforms to fetch analytics for. E.g., `?platforms=instagram,youtube,threads,pinterest,reddit`. |
| `page_id`          | string | Query         | No       | Required for Facebook analytics. The ID of the Facebook Page.                                           |
| `days`             | number | Query         | No       | **Facebook only.** Size of the insights window in days, `1`–`365` (default `30`). Metrics and time series are fetched live from Meta for exactly this range, so `?days=7`, `?days=90` or any custom value returns actual impressions/reach for that period. Ranges over 90 days are fetched in chunks transparently. |
| `page_urn`         | string | Query         | No       | **LinkedIn only.** Organization/company page URN or numeric ID to fetch analytics for. LinkedIn analytics are available **only for organization/company pages you administer** — personal profiles are **not** supported, because LinkedIn's API does not expose member-level analytics. If omitted, the first administered organization page is used. |

**Supported Platforms:**

Currently, the following platforms are supported:
*   `instagram`
*   `tiktok`
*   `Linkedin`
*   `Facebook`
*   `X` (Twitter)
*   `youtube`
*   `threads`
*   `pinterest`
*   `reddit`
*   `bluesky`
*   `google_business`

Support for additional platforms will be added in the future. If you request a platform that is not yet supported, the response will include a message indicating this for that specific platform. Reddit posting is currently unavailable; analytics for an already-connected Reddit account may still return data or `reddit_unavailable`.

> **Note:** Discord, Telegram, and the credential-based channels (Slack, Mastodon, Nostr, Lemmy, Dev.to, Hashnode, WordPress, Whop, Listmonk) do **not** support analytics. Requesting analytics for any of them returns a graceful per-platform message (e.g. `"Analytics are not supported for Discord."`) rather than an error, so a mixed request for other platforms still succeeds.

Here is an example of how to call the endpoint to get analytics for the `test` profile on Instagram, YouTube, Threads, Pinterest, and Reddit.

```bash
curl 'https://api.upload-post.com/api/analytics/test?platforms=instagram,youtube,threads,pinterest,reddit' \
--header 'Authorization: Apikey XXX...'
```

**Example Successful Response (200 OK):**

The response is a JSON object where each key corresponds to a requested platform. The value is another object containing the specific analytics data for that platform.

```json
{
    "instagram": {
        "followers": 47,
        "reach": 1250,
        "views": 3400,
        "impressions": 3400,
        "profileViews": 89,
        "likes": 120,
        "comments": 15,
        "shares": 8,
        "saves": 22,
        "reach_timeseries": [
            {
                "date": "2025-07-04",
                "value": 42
            },
            {
                "date": "2025-07-05",
                "value": 55
            },
            // ... more date entries
            {
                "date": "2025-08-02",
                "value": 38
            }
        ],
        "follower_demographics": {
            "age": { "25-34": 100, "35-44": 166, "45-54": 49 },
            "gender": { "F": 74, "M": 36, "U": 24 },
            "country": { "ES": 78, "CO": 13, "PE": 8 },
            "city": { "Madrid, Comunidad de Madrid": 18, "Barcelona, Cataluña": 2 }
        },
        "engaged_audience_demographics": {
            "age": { "25-34": 61, "35-44": 88, "45-54": 20 },
            "gender": { "F": 44, "M": 21, "U": 9 },
            "country": { "ES": 52, "CO": 7, "PE": 4 },
            "city": { "Madrid, Comunidad de Madrid": 11, "Barcelona, Cataluña": 1 }
        },
        "metric_type": "reach"
    }
}
```

**Field Descriptions for Platform Analytics:**

*   `followers`: Total number of followers.
*   `reach`: The number of unique accounts that have seen any of the profile's content.
*   `views`: Total content views (Instagram, YouTube, TikTok). For Instagram, this is the official "views" metric from the Instagram API, which replaced the deprecated "impressions" metric.
*   `impressions`: Alias for `views` on Instagram, YouTube, and TikTok. On other platforms (X, Pinterest, Threads), this represents content impressions. Kept for backwards compatibility.
*   `profileViews`: Total number of times the profile was viewed. For Instagram, this represents "Accounts Engaged" (unique accounts that interacted with the content).
*   `likes`: Total number of likes across the profile's content.
*   `comments`: Total number of comments across the profile's content.
*   `shares`: Total number of shares across the profile's content.
*   `saves`: Total number of saves across the profile's content.
*   `watch_time_minutes` (YouTube only): Total watch time in minutes over the last 30 days (YouTube Analytics `estimatedMinutesWatched`).
*   `average_view_duration_seconds` (YouTube only): Average view duration derived from watch time and views.
*   `engagement` (Bluesky only): Likes + reposts + replies + quotes received by the profile's own posts of the last 30 days. Bluesky has no impressions/reach API, so this is the headline metric and what `reach_timeseries` charts (by post date, `metric_type: "engagement"`). `following`, `posts_count` and `quotes` are also returned; `shares` are reposts and `comments` are replies.
*   `pin_clicks`: Total number of clicks on pins (Pinterest only).
*   `outbound_clicks`: Total number of clicks to external URLs from pins (Pinterest only).
*   `reach_timeseries`: An array of objects showing the daily reach or views value over the last 30 days (or, for Facebook, over the window selected with `days`). The dashboard filters this data client-side based on the selected date range.
*   `impressions_timeseries` (Facebook only): Array of `{ date, value }` objects with the **actual daily impressions** for the requested window, suitable for charting. Same window as `reach_timeseries`.
*   `period_days` (Facebook only): Echo of the insights window actually used, in days.
*   `follower_demographics` (Instagram only): Audience breakdown of the profile's **followers** as `{ age, gender, country, city }`, each a map of segment → follower count (gender values `F`/`M`/`U`; country as ISO codes). Sourced from the Instagram `follower_demographics` insight over the last 30 days. **Only returned for accounts with 100+ followers** — smaller accounts return `{}` (Meta restriction).
*   `engaged_audience_demographics` (Instagram only): Same shape as `follower_demographics` (`{ age, gender, country, city }`), but broken down by the accounts that **engaged** with the content rather than by followers. Sourced from the Instagram `engaged_audience_demographics` insight over the last 30 days. See the note below on when Meta populates it.
*   `metric_type`: Indicates what the `reach_timeseries` represents for this platform. Values: `"reach"` (Facebook, Instagram, LinkedIn), `"views"` (YouTube, TikTok, Threads), `"impressions"` (X, Pinterest), `"score"` (Reddit). Use this to avoid double-counting when aggregating across platforms.
*   `primary_impressions_field`: The field name used as the primary metric for aggregation on this platform (e.g., `"reach"` for Instagram, `"impressions"` for YouTube).
*   `available_metrics`: Array of available metric keys for this platform.
*   `metric_labels`: Object mapping metric keys to user-friendly labels for this platform.

**Instagram Demographics (`follower_demographics` and `engaged_audience_demographics`):**

Both objects have the same structure and are broken down by `age`, `gender`, `country` and `city`. They answer two different questions:

| Field | Population measured |
| --- | --- |
| `follower_demographics` | Everyone who follows the account |
| `engaged_audience_demographics` | Only the accounts that engaged with the account's content |

:::warning Meta thresholds
These are Meta restrictions, not Upload-Post ones:

*   `follower_demographics` is only populated for accounts with **100+ followers**.
*   `engaged_audience_demographics` is only populated once the **engaged** audience clears its own floor of roughly **100 accounts**, and it lands with a lag of roughly **48 hours**.

Below those thresholds Meta returns nothing, and the API surfaces that as **empty objects** (`{"age": {}, "gender": {}, "country": {}, "city": {}}` — or `{}`) rather than as an error. A brand-new or small account will therefore see empty demographics for a while; treat empty as "not enough data yet", not as a failure.
:::

For a unified total impressions metric across platforms, see the [Total Impressions](#get-apiuploadpoststotal-impressionsprofile_username) section below.

**Error Responses:**

*   `400 Bad Request`: The `platforms` query parameter is missing or invalid.
*   `401 Unauthorized`: The JWT is missing, invalid, or expired.
*   `404 Not Found`: The specified `profile_username` does not exist for the authenticated user.
*   `500 Internal Server Error`: An unexpected error occurred on the server while fetching the data.

---

---
title: 'Total Impressions API'
---

### **GET /api/uploadposts/total-impressions/profile_username**

Returns a unified "total impressions" metric for a profile, aggregated from daily analytics snapshots across all connected platforms.

---

**Method:** `GET`

**Endpoint URL:** `https://api.upload-post.com/api/uploadposts/total-impressions/profile_username`

**Description:**

This endpoint provides a single, deduplicated "total impressions" metric that intelligently combines reach and views data across platforms. Since different platforms report different types of impression metrics (Facebook and Instagram report "reach", YouTube and TikTok report "views"), this endpoint uses the most representative metric for each platform to avoid double-counting.

You can also request custom metrics aggregation by specifying which metrics to aggregate using the `metrics` parameter.

:::warning Snapshot semantics — not a daily sum for every platform
This endpoint aggregates the **daily stored snapshots** of each profile. For platforms whose snapshot stores a **rolling 30-day total** (Facebook is one), summing snapshots across a date range overstates the real value — each day's snapshot already contains the previous 30 days. For **actual Facebook impressions over an exact date range** (7/30/90 days or custom), use [`GET /api/analytics/<profile>?platforms=facebook&days=N`](#get-apianalyticsprofile_username) instead, which queries Meta live and also returns a daily `impressions_timeseries` for charting.
:::

**Authentication:**

A valid JSON Web Token (JWT) is required. Include it in the `Authorization` header:

`Authorization: Apikey <YOUR_JWT_TOKEN>`

**Parameters:**

| Parameter          | Type   | Location | Required | Description                                                                                                  |
| ------------------ | ------ | -------- | -------- | ------------------------------------------------------------------------------------------------------------ |
| `profile_username` | string | Path     | Yes      | The unique username of the profile.                                                                          |
| `date`             | string | Query    | No       | Single date in `YYYY-MM-DD` format. If provided, returns impressions for that day only.                      |
| `start_date`       | string | Query    | No       | Start of date range in `YYYY-MM-DD` format. Defaults to 30 days ago.                                        |
| `end_date`         | string | Query    | No       | End of date range in `YYYY-MM-DD` format. Defaults to today.                                                 |
| `period`           | string | Query    | No       | Shortcut for date range: `last_day`, `last_week`, `last_month`, `last_3months`, `last_year`. Overrides `start_date`/`end_date`. |
| `platform`         | string | Query    | No       | Comma-separated list of platforms to filter by. E.g., `?platform=youtube,tiktok`.                            |
| `breakdown`        | string | Query    | No       | Set to `true` to include per-platform and per-day breakdown in the response.                                 |
| `metrics`          | string | Query    | No       | Comma-separated list of metrics to aggregate. E.g., `?metrics=likes,comments,shares`. Available: `followers`, `reach`, `views`, `impressions`, `likes`, `comments`, `shares`, `saves`, `profileViews`, `video_count`, `following`, `pin_clicks`, `outbound_clicks`. When provided, returns a `metrics` object instead of `total_impressions`. |

**Metric Selection per Platform (default mode):**

| Platform   | Metric Used        | Reason                                        |
| ---------- | ------------------ | --------------------------------------------- |
| Facebook   | reach              | Reports unique account reach                  |
| Instagram  | reach              | Reports unique account reach. Note: Instagram renamed "impressions" to "views" in their API; both `views` and `impressions` fields are returned. |
| LinkedIn   | reach              | Reports unique impressions                    |
| YouTube    | impressions/views  | Reports video view counts                     |
| TikTok     | impressions/views  | Reports video view counts                     |
| X          | impressions        | Reports tweet impression counts               |
| Threads    | impressions/views  | Reports content view counts                   |
| Pinterest  | impressions        | Reports pin impression counts                 |
| Reddit     | impressions/score  | Reports post score as impressions proxy       |

**Example Request:**

```bash
curl 'https://api.upload-post.com/api/uploadposts/total-impressions/myprofile?start_date=2026-01-01&end_date=2026-01-31&breakdown=true' \
--header 'Authorization: Apikey XXX...'
```

**Example Response (200 OK):**

```json
{
    "success": true,
    "profile_username": "myprofile",
    "start_date": "2026-01-01",
    "end_date": "2026-01-31",
    "total_impressions": 45230,
    "per_platform": {
        "instagram": 12500,
        "youtube": 18730,
        "tiktok": 8000,
        "facebook": 6000
    },
    "per_day": {
        "2026-01-01": 1200,
        "2026-01-02": 1450,
        "2026-01-03": 980
    }
}
```

**Example with Period Shortcut:**

```bash
curl 'https://api.upload-post.com/api/uploadposts/total-impressions/myprofile?period=last_week' \
--header 'Authorization: Apikey XXX...'
```

```json
{
    "success": true,
    "profile_username": "myprofile",
    "start_date": "2026-02-13",
    "end_date": "2026-02-20",
    "total_impressions": 8523
}
```

**Example with Custom Metrics:**

```bash
curl 'https://api.upload-post.com/api/uploadposts/total-impressions/myprofile?period=last_month&metrics=likes,comments,shares&breakdown=true' \
--header 'Authorization: Apikey XXX...'
```

```json
{
    "success": true,
    "profile_username": "myprofile",
    "start_date": "2026-01-21",
    "end_date": "2026-02-20",
    "metrics": {
        "likes": 4520,
        "comments": 312,
        "shares": 189
    },
    "per_platform": {
        "likes": { "instagram": 2100, "youtube": 1200, "tiktok": 1220 },
        "comments": { "instagram": 150, "youtube": 80, "tiktok": 82 },
        "shares": { "instagram": 90, "youtube": 45, "tiktok": 54 }
    },
    "per_day": {
        "likes": { "2026-01-21": 150, "2026-01-22": 160 },
        "comments": { "2026-01-21": 10, "2026-01-22": 12 },
        "shares": { "2026-01-21": 6, "2026-01-22": 8 }
    }
}
```

**Example with Single Date:**

```bash
curl 'https://api.upload-post.com/api/uploadposts/total-impressions/myprofile?date=2026-02-15' \
--header 'Authorization: Apikey XXX...'
```

```json
{
    "success": true,
    "profile_username": "myprofile",
    "start_date": "2026-02-15",
    "end_date": "2026-02-15",
    "total_impressions": 1523
}
```

**Example with Platform Filter:**

```bash
curl 'https://api.upload-post.com/api/uploadposts/total-impressions/myprofile?platform=youtube,tiktok&breakdown=true' \
--header 'Authorization: Apikey XXX...'
```

```json
{
    "success": true,
    "profile_username": "myprofile",
    "start_date": "2026-01-20",
    "end_date": "2026-02-19",
    "total_impressions": 26730,
    "platforms_filter": ["youtube", "tiktok"],
    "per_platform": {
        "youtube": 18730,
        "tiktok": 8000
    },
    "per_day": {
        "2026-01-20": 850,
        "2026-01-21": 920
    }
}
```

**Error Responses:**

*   `400 Bad Request`: Invalid date format (must be `YYYY-MM-DD`) or invalid metric name.
*   `401 Unauthorized`: The JWT is missing, invalid, or expired.
*   `500 Internal Server Error`: An unexpected error occurred on the server.

---

### **GET /api/uploadposts/post-analytics/request_id**

Returns analytics for a specific post across all platforms it was published to.

---

**Method:** `GET`

**Endpoint URL:** `https://api.upload-post.com/api/uploadposts/post-analytics/request_id`

**Description:**

This endpoint provides per-post analytics by looking up the upload record and cross-referencing with stored analytics snapshots. It returns the post metadata along with platform-specific metrics at the time of posting and the latest available metrics.

**Authentication:**

A valid JSON Web Token (JWT) is required. Include it in the `Authorization` header:

`Authorization: Apikey <YOUR_JWT_TOKEN>`

**Parameters:**

| Parameter    | Type   | Location | Required | Description                              |
| ------------ | ------ | -------- | -------- | ---------------------------------------- |
| `request_id` | string | Path     | Yes      | The request ID of the upload.            |
| `platform`   | string | Query    | No       | Filter to a single platform (e.g., `?platform=x`). When provided, only metrics for that platform are fetched, which is significantly faster than fetching all platforms. |

**Example Request:**

```bash
curl 'https://api.upload-post.com/api/uploadposts/post-analytics/abc123-def456' \
--header 'Authorization: Apikey XXX...'
```

**Example Request (single platform):**

```bash
curl 'https://api.upload-post.com/api/uploadposts/post-analytics/abc123-def456?platform=youtube' \
--header 'Authorization: Apikey XXX...'
```

**Example Response (200 OK):**

```json
{
    "success": true,
    "post": {
        "request_id": "abc123-def456",
        "profile_username": "myprofile",
        "post_title": "My Video",
        "post_caption": "Check this out!",
        "media_type": "video",
        "upload_timestamp": "2026-02-10 14:30:00"
    },
    "platforms": {
        "youtube": {
            "success": true,
            "platform_post_id": "dQw4w9WgXcQ",
            "post_url": "https://youtube.com/watch?v=dQw4w9WgXcQ",
            "post_metrics": {
                "views": 5200,
                "likes": 120,
                "comments": 8,
                "favorites": 3
            },
            "post_metrics_source": "platform_api",
            "profile_snapshot_at_post_date": {
                "followers": 1500,
                "impressions": 45000,
                "likes": 320,
                "comments": 15,
                "shares": 8
            },
            "profile_snapshot_latest": {
                "followers": 1650,
                "impressions": 52000,
                "likes": 410,
                "comments": 22,
                "shares": 12
            },
            "profile_snapshot_latest_date": "2026-02-20"
        },
        "tiktok": {
            "success": true,
            "platform_post_id": "7123456789",
            "post_url": "https://tiktok.com/@user/video/7123456789",
            "post_metrics_error": "TikTok video not found (ID: 7123456789). The token may need to be refreshed.",
            "profile_snapshot_at_post_date": {
                "followers": 800,
                "impressions": 12000,
                "likes": 500,
                "comments": 30
            },
            "profile_snapshot_latest": {
                "followers": 950,
                "impressions": 18500,
                "likes": 780,
                "comments": 45
            },
            "profile_snapshot_latest_date": "2026-02-20"
        }
    }
}
```

**Per-Platform Response Fields:**

*   `success`: Whether the post was successfully published to this platform.
*   `platform_post_id`: The post's ID on the platform.
*   `post_url`: Direct URL to the published post.
*   `post_metrics`: Live metrics fetched from the platform's API for this specific post (views, likes, comments, shares, etc.).
*   `post_metrics_source`: Source of post metrics (currently `"platform_api"`).
*   `post_metrics_error`: If post-level metrics could not be fetched, this field contains a human-readable error message explaining why.
*   `profile_snapshot_at_post_date`: Profile-level metrics snapshot from the day the post was published.
*   `profile_snapshot_latest`: Most recent profile-level metrics snapshot.
*   `profile_snapshot_latest_date`: Date of the latest snapshot.

**Error Responses:**

*   `401 Unauthorized`: The JWT is missing, invalid, or expired.
*   `404 Not Found`: No post found with the given request ID.
*   `500 Internal Server Error`: An unexpected error occurred on the server.

---

### **GET /api/uploadposts/post-analytics?platform\_post\_id=**

Returns analytics for any post (including organically published posts) using its native platform ID instead of a request ID.

---

**Method:** `GET`

**Endpoint URL:** `https://api.upload-post.com/api/uploadposts/post-analytics?platform_post_id=XXXXX&platform=instagram&user=profile_username`

**Description:**

This endpoint allows you to fetch live per-post analytics using the platform's native post ID (e.g., an Instagram media ID) rather than an Upload Post `request_id`. This is useful for retrieving metrics on organically published posts that were not uploaded through the API. You can obtain `platform_post_id` values from the [GET /api/uploadposts/media](./instagram-media.md) endpoint.

:::tip
This endpoint queries the platform live, one post per call, and is limited to **100 requests per 5 minutes**. To read metrics for thousands of posts, use [`GET /api/uploadposts/post-analytics/cached`](#get-apiuploadpostspost-analyticscached) instead, which replays previously fetched results, paginated, and is not subject to that limit.
:::

**Authentication:**

A valid JSON Web Token (JWT) is required. Include it in the `Authorization` header:

`Authorization: Apikey <YOUR_JWT_TOKEN>`

**Parameters:**

| Parameter          | Type   | Location | Required | Description                              |
| ------------------ | ------ | -------- | -------- | ---------------------------------------- |
| `platform_post_id` | string | Query    | Yes      | The native post ID on the platform (e.g., Instagram media ID). |
| `platform`         | string | Query    | Yes      | The platform to query. One of: `youtube`, `tiktok`, `instagram`, `facebook`, `linkedin`, `x`, `threads`, `pinterest`, `reddit`. |
| `user`             | string | Query    | Yes      | The `profile_username` of the profile that owns the social account. |

**Example Request:**

```bash
curl 'https://api.upload-post.com/api/uploadposts/post-analytics?platform_post_id=18330983293218037&platform=instagram&user=myprofile' \
--header 'Authorization: Apikey XXX...'
```

**Example Response (200 OK):**

```json
{
    "success": true,
    "post": {
        "platform_post_id": "18330983293218037",
        "platform": "instagram",
        "profile_username": "myprofile",
        "source": "organic"
    },
    "platforms": {
        "instagram": {
            "success": true,
            "platform_post_id": "18330983293218037",
            "post_metrics": {
                "likes": 340,
                "comments": 12,
                "views": 8500,
                "reach": 6200,
                "impressions": 9100,
                "saves": 45,
                "shares": 28
            },
            "post_metrics_source": "platform_api",
            "available_metrics": ["followers", "reach", "views", "impressions", "profileViews", "likes", "comments", "shares", "saves"],
            "metric_labels": {
                "reach": "Unique Reach",
                "views": "Views",
                "impressions": "Views",
                "profileViews": "Accounts Engaged"
            },
            "primary_impressions_field": "reach"
        }
    }
}
```

**Response Fields:**

*   `post.source`: Either `"organic"` (post was not uploaded via the API) or `"api_uploaded"` (post was uploaded via the API and has an associated `request_id`).
*   `post.request_id`: Only present when `source` is `"api_uploaded"` — the original upload request ID.
*   `platforms.<platform>.post_metrics`: Live metrics fetched from the platform's API.
*   `platforms.<platform>.post_metrics_source`: Source of post metrics (currently `"platform_api"`).
*   `platforms.<platform>.post_metrics_error`: If metrics could not be fetched, a human-readable error message.
*   `platforms.<platform>.available_metrics`: List of metrics available for this platform.

#### TikTok: the full per-post breakdown

For `platform=tiktok`, `post_metrics` carries much more than the four counters.
It is the same single call, so there is nothing to opt into — the extra fields
are simply there. This applies to both per-post endpoints (`{request_id}` and
`?platform_post_id=`).

```json
{
    "platforms": {
        "tiktok": {
            "success": true,
            "platform_post_id": "7401234567890123456",
            "post_metrics": {
                "views": 40218,
                "likes": 3184,
                "comments": 96,
                "shares": 211,
                "reach": 37904,
                "favorites": 402,
                "new_followers": 128,
                "profile_views": 903,
                "full_video_watched_rate": 0.184,
                "average_time_watched": 7.4,
                "total_time_watched": 297613,
                "is_ai_generated": false,
                "retention": [
                    { "second": "1", "percentage": 0.75 },
                    { "second": "2", "percentage": 0.61 },
                    { "second": "3", "percentage": 0.49 },
                    { "second": "10", "percentage": 0.31 }
                ],
                "impression_sources": [
                    { "impression_source": "For You", "percentage": 0.977 },
                    { "impression_source": "Search", "percentage": 0.013 },
                    { "impression_source": "Follow", "percentage": 0.007 },
                    { "impression_source": "Personal Profile", "percentage": 0.002 },
                    { "impression_source": "Others", "percentage": 0.001 }
                ],
                "audience_types": [
                    { "audience_type": "New viewers", "percentage": 0.881 },
                    { "audience_type": "Returning viewers", "percentage": 0.119 }
                ]
            },
            "post_metrics_source": "platform_api"
        }
    }
}
```

| Field | Description |
| :--- | :--- |
| `reach` | Unique accounts that saw the post, as opposed to `views`. |
| `favorites` | Times the post was saved. |
| `new_followers` | Followers the account gained **from this post**. |
| `profile_views` | Profile visits attributed to this post. |
| `full_video_watched_rate` | Fraction of viewers who watched to the end (`0.184` = 18.4%). |
| `average_time_watched` | Average watch time, in seconds. |
| `total_time_watched` | Total watch time across all viewers, in seconds. |
| `is_ai_generated` | TikTok's own AI-content flag on the post. |
| `retention` | The retention curve, one point per second, **already ordered by second**. TikTok's own ordering is not numeric, so a chart drawn from the raw upstream data would plot second 10 before second 2. |
| `impression_sources` | Where the views came from: `For You`, `Search`, `Follow`, `Personal Profile`, `Sound`, `Direct Message`, `Others`. In the example 97.7% of the reach is For You. |
| `audience_types` | New vs returning viewers, and followers vs non-followers. |
| `audience` | Present when TikTok reported it: `countries`, `cities` and `genders` of this post's viewers. |

All `percentage` values are fractions of 1, not numbers out of 100.

:::info A missing field is not a zero
Only what TikTok actually reported is included. A field TikTok did not answer for
is **omitted**, never filled with `0` or an empty array — an empty `retention`
rendered as a chart reads as "nobody watched", which is a different fact from
"not reported yet". Read the keys defensively, and expect a post published
minutes ago to carry few of them: TikTok fills them in over the following hours.
:::

**Error Responses:**

*   `400 Bad Request`: Missing or invalid query parameters.
*   `401 Unauthorized`: The JWT is missing, invalid, or expired.
*   `404 Not Found`: User or profile not found.
*   `500 Internal Server Error`: An unexpected error occurred on the server.

---

### **GET /api/uploadposts/post-analytics/cached**

Reads back, in bulk, per-post metrics that Upload-Post already fetched for you, instead of calling the social platforms again.

---

**Method:** `GET`

**Endpoint URL:** `https://api.upload-post.com/api/uploadposts/post-analytics/cached`

**Description:**

The live per-post endpoints (`/api/uploadposts/post-analytics/{request_id}` and `/api/uploadposts/post-analytics?platform_post_id=`) query the platform APIs on every call, one post at a time, and are therefore subject to the platform analytics rate limit of **100 requests per 5 minutes**. Walking an account with thousands of posts through them is not practical.

This endpoint reads instead from a **write-through cache**: every time a post's metrics are fetched through one of the live endpoints, the result is stored. This endpoint replays those stored values, many posts per call, paginated, and is **not subject to the 100 requests / 5 minutes platform analytics rate limit**, because it never touches the platforms during the request. It is designed for re-reading a large back catalogue you have already fetched once.

:::warning There is no background refresh
This cache is filled as a side effect of live reads. Nothing updates it on its own. Three consequences:

*   A post appears here **only after it has been fetched at least once** through a live per-post endpoint. A post you have never queried live will not be here at all.
*   `captured_at` is **the last time that post was fetched live** — not "this morning". A post you last read a month ago will still show a month-old value.
*   To refresh a post, call the **live** endpoint for it. The cache updates as a side effect of that call.

So the pattern is: fetch live once (or whenever you want fresh numbers), then re-read as many times as you like from here for free. That is where the saving is — customers typically re-read the same post several times a day.

If this endpoint seems to "stop" at some date, that date is simply the last time you called a live endpoint — publishing more posts does not move it. And because `since`/`until` filter the capture day, a post published yesterday but never read live falls outside every range you can ask for.

Every response tells you where you stand: `published_posts_in_range` is how many posts you actually published in the range, and `cached_posts_in_range` is how many of them are in the cache. When the first is much larger than the second, you have posts you have never read live — that is the signal to seed them, not a bug.
:::

**Authentication:**

A valid JSON Web Token (JWT) is required. Include it in the `Authorization` header:

`Authorization: Apikey <YOUR_JWT_TOKEN>`

**Parameters:**

| Parameter  | Type    | Location | Required | Description                                                                                     |
| ---------- | ------- | -------- | -------- | ------------------------------------------------------------------------------------------------ |
| `user`     | string  | Query    | Yes      | The `profile_username` of the profile to read snapshots for.                                    |
| `platform` | string  | Query    | No       | Restrict the result to a single platform. One of: `instagram`, `tiktok`, `youtube`, `facebook`, `linkedin`, `threads`, `pinterest`, `reddit`. When omitted, all platforms are returned and the response echoes `"platform": null`. |
| `limit`    | integer | Query    | No       | Number of posts per page. Defaults to `50`, maximum `200`.                                       |
| `cursor`   | string  | Query    | No       | Opaque token identifying the next page. Pass back exactly what the previous response returned in `next_cursor`. Do not build or parse these tokens yourself — an invalid cursor returns `400`. |
| `since`    | string  | Query    | No       | Start of the date range in `YYYY-MM-DD` format. Filters on the day the metrics were **captured** (the live read), not the day the post was published. Defaults to 30 days ago. |
| `until`    | string  | Query    | No       | End of the date range in `YYYY-MM-DD` format. Filters on the capture day, not the publish day. Defaults to today. |

**Example Request:**

<Tabs groupId="lang">
<TabItem value="curl" label="cURL">

```bash
curl 'https://api.upload-post.com/api/uploadposts/post-analytics/cached?user=influencersde&limit=50' \
--header 'Authorization: Apikey XXX...'
```

</TabItem>
<TabItem value="python" label="Python">

```python
import requests

response = requests.get(
    "https://api.upload-post.com/api/uploadposts/post-analytics/cached",
    headers={"Authorization": "Apikey XXX..."},
    params={"user": "influencersde", "limit": 50},
)
print(response.json())
```

</TabItem>
<TabItem value="js" label="JavaScript">

```javascript
const params = new URLSearchParams({ user: "influencersde", limit: "50" });

const response = await fetch(
  `https://api.upload-post.com/api/uploadposts/post-analytics/cached?${params}`,
  { headers: { Authorization: "Apikey XXX..." } }
);
console.log(await response.json());
```

</TabItem>
</Tabs>

**Example Response (200 OK):**

```json
{
    "success": true,
    "profile_username": "influencersde",
    "platform": null,
    "since": "2026-06-28",
    "until": "2026-07-28",
    "source": "snapshot_cache",
    "posts": [
        {
            "post_id": "uAAYuyTX4P0",
            "platform": "youtube",
            "profile_username": "influencersde",
            "date": "2026-07-28",
            "captured_at": "2026-07-28T11:17:06.901000",
            "metrics": {
                "views": 412,
                "likes": 3,
                "comments": 0,
                "favorites": 0
            },
            "post_url": "https://www.youtube.com/watch?v=uAAYuyTX4P0",
            "media_type": "video",
            "upload_timestamp": "2026-07-17T08:01:50.228000"
        }
    ],
    "limit": 50,
    "next_cursor": "eyJkIjoiMjAyNi0wNy0yOCIsInAiOi...",
    "has_more": true,
    "cached_posts_in_range": 128,
    "published_posts_in_range": 341
}
```

**Response Fields:**

*   `profile_username`: The profile the snapshots belong to.
*   `platform`: The platform filter that was applied, or `null` when no `platform` was requested.
*   `since` / `until`: The effective date range covered by the response.
*   `source`: Always `"snapshot_cache"` — a marker that these numbers were replayed from the cache, not fetched from the platform during this request.
*   `cached_posts_in_range`: How many distinct posts of this profile are in the cache for the range, regardless of how many fit on this page.
*   `published_posts_in_range`: How many posts this profile actually published in the range. Compare it with `cached_posts_in_range` to see how much you have yet to seed. Note the two count against different dates — the cache by capture day, this one by publish day — so a small difference is normal and a large one means unseeded posts. Both are `null` in the rare case the count could not be computed; the rest of the response is unaffected.
*   `posts`: Array of per-post snapshot rows (see below).
*   `limit`: The effective page size that was applied.
*   `next_cursor`: Token to pass as `cursor` on the next request. `null` when there are no more pages.
*   `has_more`: `true` when another page is available.

**Per-Post Fields:**

| Field              | Type        | Description                                                                 |
| ------------------ | ----------- | ----------------------------------------------------------------------------- |
| `post_id`          | String      | The post's native ID on the platform.                                        |
| `platform`         | String      | The platform this row belongs to.                                            |
| `profile_username` | String      | The profile that owns the post.                                              |
| `date`             | String      | Snapshot date (`YYYY-MM-DD`) this row belongs to.                            |
| `captured_at`      | String      | ISO 8601 timestamp of the last time this post was fetched live from the platform. **Use this to judge freshness** — nothing refreshes it in the background. |
| `metrics`          | Object      | The metrics captured for this post. **Keys vary per platform** — see below.  |
| `post_url`         | String/null | Direct URL to the published post.                                            |
| `media_type`       | String/null | Type of media (e.g. `video`, `image`).                                       |
| `upload_timestamp` | String/null | ISO 8601 timestamp of when the post was originally published.                |

:::info Metrics keys vary per platform
`metrics` mirrors what each platform exposes for a post. YouTube returns `views`, `likes`, `comments`, `favorites` plus, when the YouTube Analytics report is available, `watch_time_minutes`, `average_view_duration_seconds` and `average_view_percentage`; Bluesky (use the `at://` URI returned on upload as `platform_post_id`) returns `likes`, `reposts`, `replies`, `quotes` and `engagement`; Instagram returns reach/saves/shares-style keys; TikTok, Pinterest and the rest each return their own set. Facebook posts return `reactions`, `likes`, `comments`, `shares` and — for Page posts where Meta serves post insights — `reach` (unique accounts, via `post_total_media_view_unique` with a fallback to the pre-2026 `post_impressions_unique`), so engagement rate can be computed per post. Read the keys present on each row rather than assuming a fixed schema, and treat a missing key as "not reported by that platform".
:::

**Paginating Through Everything:**

<Tabs groupId="lang">
<TabItem value="curl" label="cURL">

```bash
API_KEY="XXX..."
CURSOR=""

while :; do
  URL="https://api.upload-post.com/api/uploadposts/post-analytics/cached?user=influencersde&limit=200"
  [ -n "$CURSOR" ] && URL="$URL&cursor=$CURSOR"

  RESPONSE=$(curl -s "$URL" -H "Authorization: Apikey $API_KEY")
  echo "$RESPONSE" | jq -r '.posts[] | "\(.platform) \(.post_id)"'

  [ "$(echo "$RESPONSE" | jq -r '.has_more')" = "true" ] || break
  CURSOR=$(echo "$RESPONSE" | jq -r '.next_cursor')
done
```

</TabItem>
<TabItem value="python" label="Python">

```python
import requests

API_KEY = "XXX..."
all_posts = []
cursor = None

while True:
    params = {"user": "influencersde", "limit": 200, "since": "2026-01-01"}
    if cursor:
        params["cursor"] = cursor

    response = requests.get(
        "https://api.upload-post.com/api/uploadposts/post-analytics/cached",
        headers={"Authorization": f"Apikey {API_KEY}"},
        params=params,
    )
    data = response.json()
    all_posts.extend(data["posts"])

    if not data.get("has_more"):
        break
    cursor = data["next_cursor"]

print(f"Fetched {len(all_posts)} post snapshots")
```

</TabItem>
<TabItem value="js" label="JavaScript">

```javascript
const API_KEY = "XXX...";
const allPosts = [];
let cursor = null;

while (true) {
  const params = new URLSearchParams({
    user: "influencersde",
    limit: "200",
    since: "2026-01-01",
  });
  if (cursor) params.set("cursor", cursor);

  const response = await fetch(
    `https://api.upload-post.com/api/uploadposts/post-analytics/cached?${params}`,
    { headers: { Authorization: `Apikey ${API_KEY}` } }
  );
  const data = await response.json();
  allPosts.push(...data.posts);

  if (!data.has_more) break;
  cursor = data.next_cursor;
}

console.log(`Fetched ${allPosts.length} post snapshots`);
```

</TabItem>
</Tabs>

**Live vs. Cached:**

| | `GET /api/uploadposts/post-analytics` (live) | `GET /api/uploadposts/post-analytics/cached` |
| --- | --- | --- |
| Data source | Platform API, in real time | Upload-Post's write-through cache |
| Freshness | Current | As of your last live read (`captured_at` tells you) |
| Rate limit | 100 requests / 5 minutes | Not subject to the platform analytics rate limit |
| Scope per call | One post | Up to 200 posts, paginated |
| Covers a post published minutes ago | Yes | Only after you fetch it live once |
| Best for | Refreshing a post's numbers | Re-reading in bulk what you already fetched |

**Error Responses:**

*   `400 Bad Request`: Missing `user`, an invalid `platform`, a malformed `since`/`until` date, or an invalid/expired `cursor`.
*   `401 Unauthorized`: The JWT is missing, invalid, or expired.
*   `404 Not Found`: User or profile not found.
*   `500 Internal Server Error`: An unexpected error occurred on the server.

---

### **GET /api/uploadposts/platform-metrics**

Returns the available metrics configuration for all supported platforms.

---

**Method:** `GET`

**Endpoint URL:** `https://api.upload-post.com/api/uploadposts/platform-metrics`

**Description:**

This public endpoint returns the metrics configuration for each social platform, including which metrics are available and which field is used as the primary "impressions" metric for aggregation.

**Example Response (200 OK):**

```json
{
    "instagram": {
        "primary_impressions_field": "reach",
        "available_metrics": ["followers", "reach", "views", "impressions", "profileViews", "likes", "comments", "shares", "saves"],
        "metric_labels": {
            "reach": "Unique Reach",
            "views": "Views",
            "impressions": "Views",
            "profileViews": "Accounts Engaged"
        }
    },
    "youtube": {
        "primary_impressions_field": "impressions",
        "available_metrics": ["followers", "impressions", "likes", "comments", "shares"],
        "metric_labels": {
            "impressions": "Video Views",
            "followers": "Subscribers"
        }
    }
}
```


---
# Get Facebook Pages
URL: https://docs.upload-post.com/api/get-facebook-pages

This endpoint is crucial for uploads, as it provides you with the necessary `ID` to specify which Facebook Page you want to send your content to.

### **Get Facebook Pages**

This endpoint allows you to get a list of all Facebook pages a user has access to through their connected accounts. This is a necessary step if you want to post to a specific page, as you will need its `ID`.

-   **Method:** `GET`
-   **Endpoint:** `/api/uploadposts/facebook/pages`
-   **Authentication:**
    -   **API Key** in the `Authorization` header.
        -   `Authorization: Apikey <YOUR_API_KEY>`

-   **Query Parameters:**

| Parameter | Type   | Description                                                                                                                                                             | Required |
| :-------- | :----- | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :--- |
| `profile` | string | **Optional**. The profile's `username`. If provided, the API will return only the Facebook pages associated with the Facebook account linked to that profile. | No   |

-   **Successful Response (`200 OK`)**

The response will include a list of objects, where each object represents a Facebook page.

```json
{
  "success": true,
  "pages": [
    {
      "id": "109876543210987",
      "name": "My Business Page",
      "picture": "https://url.to/profile/picture.jpg",
      "account_id": "1234567890123456",
      "followers": 12840,
      "likes": 11902
    },
    {
      "id": "208765432109876",
      "name": "Travel Blog",
      "picture": "https://url.to/another/picture.jpg",
      "account_id": "1234567890123456",
      "followers": 530,
      "likes": 498
    }
  ]
}
```

-   **Additional Notes:**
    -   To post on a Facebook page, you must pass the page `id` in the `facebook_page_id` parameter of the upload endpoint (`/api/upload` or `/api/upload_photos`), or [pin the Page to the profile](./facebook-page.md) once and omit the parameter. 

`followers` (Page followers) and `likes` (Page likes) give the audience size of each Page so integrators can show it next to the picker; either is `null` when Meta does not report it for that Page.


---
# Get Google Business Locations
URL: https://docs.upload-post.com/api/get-google-business-locations

This endpoint queries the Google Business Profile API in real-time to return all available locations for a connected account. Use this to let users choose which location to post to.

### **Get Google Business Locations**

-   **Method:** `GET`
-   **Endpoint:** `/api/uploadposts/google-business/locations`
-   **Authentication:**
    -   **API Key** in the `Authorization` header.
        -   `Authorization: Apikey <YOUR_API_KEY>`

-   **Query Parameters:**

| Parameter | Type   | Description                                                                                                                                                             | Required |
| :-------- | :----- | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :--- |
| `profile` | string | **Optional**. The profile's `username`. If provided, the API will return only the locations associated with the Google Business account linked to that profile. | No   |

-   **Successful Response (`200 OK`)**

```json
{
  "success": true,
  "locations": [
    {
      "name": "accounts/123456789/locations/111111111",
      "title": "Main Street Store",
      "account_id": "accounts_123456789_111111111"
    },
    {
      "name": "accounts/123456789/locations/222222222",
      "title": "Downtown Branch",
      "account_id": "accounts_123456789_111111111"
    }
  ]
}
```

| Field      | Description                                                    |
| :--------- | :------------------------------------------------------------- |
| `name`     | The Google Business location identifier (used as `gbp_location_id` in uploads) |
| `title`    | Display name of the business location                          |
| `account_id` | The internal Upload-Post account key                        |

---

### **Using Locations in Upload Requests**

The `gbp_location_id` parameter tells the API which location to post to. First list the available locations using the endpoint above, then include the selected location's `name` as `gbp_location_id` in your upload request.

| Parameter        | Type   | Description                                              | Required |
| :--------------- | :----- | :------------------------------------------------------- | :------- |
| `gbp_location_id` | string | The location to post to. Must be a valid location `name` from the locations list. | No*  |

This parameter works with all upload endpoints (`/api/upload`, `/api/upload_photos`, `/api/upload_text`).

If `gbp_location_id` is omitted, the API lists locations: **one** is used automatically, **several** returns an error asking you to pick, **zero** returns an error. Multi-location accounts can [pin one location](./google-business-location.md) instead of sending the id on every request — the pin wins, including on scheduled posts.

**Example:**

```bash
curl -X POST https://api.upload-post.com/api/upload_text \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -F 'user=my-profile' \
  -F 'platform[]=google_business' \
  -F 'title=Hello from our Downtown Branch!' \
  -F 'gbp_location_id=accounts/123456789/locations/222222222' \
  -F 'gbp_topic_type=STANDARD'
```

---

### **Local Posts vs. the Media/Gallery Tab**

A Google Business upload can target two different places on the location:

| Destination | How to select it | Result |
| :---------- | :--------------- | :----- |
| **Updates tab** (Local Post) | Default. Nothing extra to send. | Creates a Local Post, optionally `STANDARD`, `EVENT` or `OFFER`. |
| **Media / Gallery tab** | `gbp_post_type=MEDIA` (also accepts `PHOTO` or `GALLERY`), or `gbp_upload_to_gallery=true` | Uploads the attached photo straight to the location's photo gallery. |

When targeting the Media/Gallery tab you can also send `gbp_media_category` (alias `media_category`) to file the photo under a category — `COVER`, `PROFILE`, `LOGO`, `EXTERIOR`, `INTERIOR`, `PRODUCT`, `AT_WORK`, `FOOD_AND_DRINK`, `MENU`, `COMMON_AREA`, `ROOMS`, `TEAMS` or `ADDITIONAL` (the default).

```bash
curl -X POST https://api.upload-post.com/api/upload_photos \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -F 'user=my-profile' \
  -F 'platform[]=google_business' \
  -F 'photos[]=@/path/to/storefront.jpg' \
  -F 'gbp_location_id=accounts/123456789/locations/222222222' \
  -F 'gbp_post_type=MEDIA' \
  -F 'gbp_media_category=EXTERIOR'
```

➡️ Full parameters, response shape and errors: [Publishing to the Media/Gallery tab](./upload-photo.md#publishing-to-the-mediagallery-tab).

-   **Additional Notes:**
    -   Locations are queried live from the Google Business API each time you call this endpoint.
    -   The endpoint handles token refresh automatically if the stored access token has expired.
    -   Connect your Google account via OAuth first — the connection stores your credentials, and this endpoint uses them to fetch locations in real-time.
    -   Works the same way as Facebook pages: connect once, then select which location to post to on each upload.


---
# Get Instagram Publishing Limit
URL: https://docs.upload-post.com/api/get-instagram-publishing-limit

# Get Instagram Publishing Limit

Returns the connected Instagram account's content-publishing quota, capped at Upload-Post's 24-hour limit (50 posts/24h per [Limit of uploads](../guides/limit-of-uploads.md)).

-   **Method:** `GET`
-   **Endpoint:** `/api/uploadposts/instagram/publishing_limit`
-   **Authentication:** API Key — `Authorization: Apikey <YOUR_API_KEY>`

### Query Parameters

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `profile` | string | Yes | The profile's `username`. Alias: `user`. |
| `refresh` | boolean | No | When `true`, skip the cache and fetch from Instagram. |

### Example Request

```bash
curl -G 'https://api.upload-post.com/api/uploadposts/instagram/publishing_limit' \
  -H 'Authorization: Apikey your-api-key-here' \
  -d 'profile=your_profile'
```

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "platform": "instagram",
  "profile": "your_profile",
  "quota_usage": 12,
  "quota_total": 50,
  "quota_duration": 86400,
  "remaining": 38,
  "limit_reached": false,
  "fetched_at": "2026-09-08T12:00:00Z",
  "cached": true
}
```

When the quota is exhausted, uploads fail with `error_code: "instagram_publishing_limit_reached"`.


---
# Get LinkedIn Pages
URL: https://docs.upload-post.com/api/get-linkedin-pages

This endpoint is crucial for uploads, as it provides you with the necessary `ID` to specify which LinkedIn Page you want to send your content to.

### **Get LinkedIn Pages**

Retrieves a list of LinkedIn company pages associated with the authenticated user's account(s).

-   **Method:** `GET`
-   **Endpoint:** `/api/uploadposts/linkedin/pages`
-   **Authentication:**
    -   **Type:** Apikey Token
    -   **Header:** `Authorization: Apikey <YOUR_TOKEN>`

-   **Query Parameters:**

| Parameter | Type   | Description                                                                                                                                                             | Required |
| :-------- | :----- | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :--- |
| `profile` | string | **Optional**. The username of a specific profile. If provided, the endpoint will return only the LinkedIn pages associated with the LinkedIn account linked to that profile. If omitted, it will return pages from all LinkedIn accounts connected to the user. | No   |

-   **Successful Response (`200 OK`)**

A JSON object containing a list of the user's LinkedIn pages.

```json
{
  "success": true,
  "pages": [
    {
      "id": "urn:li:organization:12345678",
      "name": "Your Company Name",
      "picture": "https://media.licdn.com/dms/image/C4D0BAQG.../feedshare-logo_300_300.png",
      "account_id": "urn:li:organization:1122334455",
      "vanityName": "your-company-name",
      "followers": 5321
    },
    {
      "id": "urn:li:organization:87654321",
      "name": "Another Company Page",
      "picture": "https://media.licdn.com/dms/image/D4E0BAQ.../feedshare-logo_300_300.png",
      "account_id": "urn:li:organization:1122334455",
      "vanityName": "another-company",
      "followers": 210
    }
  ]
}
```

-   **Field Descriptions:**
    -   `id`: The unique identifier (URN) for the LinkedIn organization. This is the value you should use when specifying a `target_linkedin_page_id` in other API calls.
    -   `name`: The display name of the LinkedIn page.
    -   `picture`: The URL of the page's logo. Can be `null`.
    -   `account_id`: The internal identifier for the user's connected LinkedIn account in the Upload-Post system.
    -   `vanityName`: The custom "vanity" URL of the page (e.g., the part that comes after `linkedin.com/company/`). Can be `null`.

-   **Error Responses:**
    -   **401 Unauthorized:** If the `Authorization` header is missing or the token is invalid.
    -   **404 Not Found:**
        -   If the user associated with the token is not found.
        -   If a `profile` username is provided but not found for that user.
        -   If no LinkedIn accounts are connected to the user or the specified profile.
        -   If no LinkedIn pages are found for the connected accounts.
    -   **500 Internal Server Error:** If there's an issue communicating with the LinkedIn API or an unexpected server error occurs. 

`followers` is the organization's follower count (LinkedIn `networkSizes`), or `null` when LinkedIn does not return it for that page.


---
# Get Pinterest Boards
URL: https://docs.upload-post.com/api/get-pinterest-boards

This endpoint is crucial for uploads, as it provides you with the necessary `ID` to specify which Pinterest Board you want to send your content to.

### **Get Pinterest Boards**

This endpoint allows you to get a list of all boards (public and secret) from a connected Pinterest account. You will need a board `ID` to post a Pin to it.

-   **Method:** `GET`
-   **Endpoint:** `/api/uploadposts/pinterest/boards`
-   **Authentication:**
    -   **API Key** in the `Authorization` header.
        -   `Authorization: Apikey <YOUR_API_KEY>`

-   **Query Parameters:**

| Parameter | Type   | Description                                                                                                                                                             | Required |
| :-------- | :----- | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :--- |
| `profile` | string | **Optional**. The profile's `username`. If provided, the API will return only the boards from the Pinterest account linked to that specific profile.                 | No       |

-   **Successful Response (`200 OK`)**

The response will include a list of objects, where each object represents a Pinterest board.

```json
{
  "success": true,
  "boards": [
    {
      "id": "987654321098765432",
      "name": "Summer Recipes"
    },
    {
      "id": "876543210987654321",
      "name": "Design Inspiration"
    }
  ],
  "pinterest_account_used": "pinterest_username"
}
```

-   **Additional Notes:**
    -   To post a Pin, you must pass the board `id` in the `pinterest_board_id` parameter of the upload endpoint (`/api/upload` or `/api/upload_photos`).
    -   If a `profile` is not specified, the API will use the first Pinterest account it finds connected to the user. The response will tell you which account was used in the `pinterest_account_used` field.
    -   Secret boards cannot be posted to (`error_code: "pinterest_secret_board"`).

### Create a board

```
POST /api/uploadposts/pinterest/boards
```

JSON body: `{ "user": "<profile>", "name": "<board name>", "description": "...", "privacy": "PUBLIC"|"PROTECTED" }`. `SECRET` is rejected (400). Returns **201**.

### List board sections

```
GET /api/uploadposts/pinterest/boards/<board_id>/sections?profile=<user>
```

Returns `{success, board_id, sections:[{id,name}], profile}`.

### Create a board section

```
POST /api/uploadposts/pinterest/boards/<board_id>/sections
```

JSON body: `{ "user": "<profile>", "name": "<1–180 characters>" }`. Returns **201**. Pass the section id as `pinterest_board_section_id` on upload.


---
# Reddit Detailed Posts
URL: https://docs.upload-post.com/api/get-reddit-detailed-posts

### **GET /api/uploadposts/reddit/detailed-posts/**

Retrieves detailed posts from a Reddit account connected to a profile, including complete media information (images, galleries, and videos).

:::warning Reddit is currently unavailable
This endpoint currently returns **HTTP 503** with `error_code: "reddit_unavailable"`, same as Reddit uploads, OAuth, and comments.
:::

---

**Method:** `GET`

**Endpoint URL:** `https://api.upload-post.com/api/uploadposts/reddit/detailed-posts/`

**Authentication:**

A valid JSON Web Token (JWT) is required for authentication. The token must be included in the `Authorization` header as a Bearer token.

`Authorization: Bearer <YOUR_JWT_TOKEN>`

**Query Parameters:**

| Parameter          | Type   | Required | Description                                                      |
| ------------------ | ------ | -------- | ---------------------------------------------------------------- |
| `profile_username` | string | Yes      | Username of the profile that has the Reddit account connected.   |

**Example Request:**

```bash
curl 'https://api.upload-post.com/api/uploadposts/reddit/detailed-posts/?profile_username=mi_perfil' \
--header 'Authorization: Bearer <YOUR_JWT_TOKEN>'
```

**Example Successful Response (200 OK):**

```json
{
  "posts": [
    {
      "id": "1abc123",
      "title": "Título del post",
      "subreddit": "programming",
      "body": "Contenido del post o URL si es un link post",
      "likes": 150,
      "comments": 42,
      "impressions": 1500,
      "has_image": true,
      "has_video": false,
      "media": [
        {
          "type": "image",
          "url": "https://i.redd.it/example.jpg",
          "width": 1920,
          "height": 1080
        }
      ],
      "url": "https://www.reddit.com/r/programming/comments/1abc123/titulo_del_post/",
      "created_at": "2026-01-15T10:30:00+00:00",
      "thumbnail": "https://b.thumbs.redditmedia.com/example.jpg"
    }
  ]
}
```

**Response Fields:**

| Field         | Type     | Description                                                   |
| ------------- | -------- | ------------------------------------------------------------- |
| `id`          | string   | Unique identifier of the Reddit post.                         |
| `title`       | string   | Title of the post.                                            |
| `subreddit`   | string   | Name of the subreddit where the post was published.           |
| `body`        | string   | Content of the post or URL if it's a link post.               |
| `likes`       | integer  | Number of upvotes on the post.                                |
| `comments`    | integer  | Number of comments on the post.                               |
| `impressions` | integer  | Number of views (uses `view_count` if available, otherwise `score`). |
| `has_image`   | boolean  | Indicates if the post contains images.                        |
| `has_video`   | boolean  | Indicates if the post contains video.                         |
| `media`       | array    | Array of media objects attached to the post.                  |
| `url`         | string   | Direct URL to the post on Reddit.                             |
| `created_at`  | string   | ISO 8601 timestamp of when the post was created.              |
| `thumbnail`   | string   | URL of the post's thumbnail image.                            |

**Media Types:**

| Type             | Description                      | Additional Fields               |
| ---------------- | -------------------------------- | ------------------------------- |
| `image`          | Individual or gallery image      | `width`, `height`               |
| `video`          | Video hosted on Reddit           | `width`, `height`, `duration`   |
| `external_video` | External video (YouTube, etc.)   | `thumbnail`, `provider`         |

**Error Responses:**

| Code | Message                                              | Description                                      |
| ---- | ---------------------------------------------------- | ------------------------------------------------ |
| 400  | `Query parameter "profile_username" is required.`   | The required parameter is missing.               |
| 400  | `Profile 'X' has no Reddit account connected.`      | The profile does not have a linked Reddit account. |
| 400  | `Reddit account 'X' not found.`                     | The Reddit account does not exist for the user.  |
| 500  | `An internal server error occurred.`                | Internal server error.                           |

**Notes:**

*   Supports automatic pagination, fetching up to 2000 posts (20 pages × 100 posts).
*   Gallery posts include all images in the `media` array.
*   Image URLs are already unescaped (no `&amp;`).
*   `impressions` uses `view_count` if available, otherwise uses `score` as a fallback.


---
# Get Threads Publishing Limit
URL: https://docs.upload-post.com/api/get-threads-publishing-limit

# Get Threads Publishing Limit

Returns the connected Threads account's publishing quota (250 posts / 1000 replies per 24 hours).

-   **Method:** `GET`
-   **Endpoint:** `/api/uploadposts/threads/publishing_limit`
-   **Authentication:** API Key — `Authorization: Apikey <YOUR_API_KEY>`

### Query Parameters

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `user` | string | Yes | The profile's `username`. |
| `refresh` | boolean | No | When `true`, skip the cache and fetch from Threads. |

### Example Request

```bash
curl -G 'https://api.upload-post.com/api/uploadposts/threads/publishing_limit' \
  -H 'Authorization: Apikey your-api-key-here' \
  -d 'user=your_profile'
```

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "platform": "threads",
  "posts": { "used": 10, "limit": 250, "remaining": 240, "window_seconds": 86400 },
  "replies": { "used": 3, "limit": 1000, "remaining": 997, "window_seconds": 86400 },
  "fetched_at": "2026-09-08T12:00:00Z",
  "cached": true
}
```


---
# Get TikTok Locations
URL: https://docs.upload-post.com/api/get-tiktok-locations

# Get TikTok Locations

Searches TikTok's place database and returns the `location_id` you need in order
to tag a location on a post. Pass the chosen entry as `tiktok_location_id` +
`tiktok_location_name` on the upload request.

-   **Method:** `GET`
-   **Endpoint:** `/api/uploadposts/tiktok/locations`
-   **Authentication:**
    -   **API Key** in the `Authorization` header.
        -   `Authorization: Apikey <YOUR_API_KEY>`

### Query Parameters

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `profile` | string | Yes | The profile's `username`. Must have a TikTok account connected. |
| `q` | string | Yes | Free-text search query (venue, brand or place name). Max **100 characters**. |

### Example Request

```bash
curl -G 'https://api.upload-post.com/api/uploadposts/tiktok/locations' \
  -H 'Authorization: Apikey your-api-key-here' \
  -d 'profile=your_profile' \
  -d 'q=Museo del Prado'
```

### Successful Response (`200 OK`)

TikTok returns at most **20 matches** per query.

```json
{
  "success": true,
  "query": "Museo del Prado",
  "locations": [
    {
      "location_id": "v2_2f4a1b9c8e",
      "location_name": "Museo Nacional del Prado",
      "location_address": "C. de Ruiz de Alarcón, 23, 28014 Madrid, Spain"
    },
    {
      "location_id": "v2_7c1d0a5f32",
      "location_name": "Prado Museum Gift Shop",
      "location_address": "Paseo del Prado, s/n, 28014 Madrid, Spain"
    }
  ]
}
```

| Field | Description |
| :--- | :--- |
| `location_id` | Opaque TikTok place id. Pass it as `tiktok_location_id`. |
| `location_name` | Display name. **Must** be sent alongside the id as `tiktok_location_name`. |
| `location_address` | Postal address, useful for disambiguating results in a picker. |

### Error Responses

- `400 Bad Request` — missing `profile` or `q`, or a query longer than 100 characters.

```json
{ "success": false, "message": "q (search query) is required" }
```

```json
{ "success": false, "message": "q must be at most 100 characters" }
```

- `404 Not Found` — no profile with that username exists under your API key.

```json
{
  "success": false,
  "message": "Profile 'your_profile' not found.",
  "error_code": "PROFILE_NOT_FOUND"
}
```

- `400 Bad Request` — the profile's TikTok connection does not support this
  endpoint (it lacks the `location` capability). Reconnect the account.

```json
{
  "success": false,
  "message": "Profile 'your_profile' has no TikTok connection that supports this endpoint. Reconnect your TikTok account from Manage Users.",
  "error_code": "tiktok_reconnect_required"
}
```

- `409 Conflict` — the TikTok token expired and the account must be reconnected
  (`"reauth_required": true`).
- `502 Bad Gateway` — TikTok rejected the search; the upstream message is
  returned verbatim in `message`.

An empty `locations` array means TikTok found no match — it is a `200`, not an error.

### Using a location on an upload

```bash
curl -X POST https://api.upload-post.com/api/upload \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user=your_profile' \
  -F 'platform[]=tiktok' \
  -F 'title=A morning at the museum' \
  -F 'video=@/path/to/video.mp4' \
  -F 'tiktok_location_id=v2_2f4a1b9c8e' \
  -F 'tiktok_location_name=Museo Nacional del Prado'
```

### Additional Notes

- `tiktok_location_name` is **required** whenever `tiktok_location_id` is sent.
  Sending the id alone is rejected.
- Do not build or guess `location_id` values: they are only valid when returned
  by this endpoint for the same connected account.
- Location tagging works on both video and photo posts.


---
# Get TikTok Trending Music
URL: https://docs.upload-post.com/api/get-tiktok-music

# Get TikTok Trending Music

Returns trending tracks from TikTok's **Commercial Music Library (CML)** — the
catalogue of songs cleared for commercial use. The `id` of a track is what you
pass as `tiktok_music_id` when uploading a video.

:::tip Looking for a specific song?
TikTok has no music search endpoint — this one only returns trending charts. To
find a track by name or artist, use
[Search TikTok Music](./search-tiktok-music.md), which searches the charts
Upload-Post caches on your behalf.
:::

-   **Method:** `GET`
-   **Endpoint:** `/api/uploadposts/tiktok/music/trending`
-   **Authentication:**
    -   **API Key** in the `Authorization` header.
        -   `Authorization: Apikey <YOUR_API_KEY>`

### Query Parameters

| Parameter | Type | Required | Description | Default |
| :--- | :--- | :--- | :--- | :--- |
| `profile` | string | Yes | The profile's `username`. Must have a TikTok account connected. | - |
| `genre` | string | No | Filter by genre. Accepts `ALL` plus TikTok's genre enum, verbatim (slashes and ampersands included): `ROCK`, `POP`, `LATIN`, `METAL`, `ELECTRONIC`, `HIP_HOP/RAP`, `ALTERNATIVE/INDIE`, `FOLK`, `R&B/SOUL`, `COUNTRY`, `CLASSICAL`, `JAZZ`, `REGGAE`, `CHILDHOOD`, `BLUES`, `EASY_LISTENING`, `NEW_AGE`, `WORLD_MUSIC`, `EXPERIMENTAL`, `DEVOTIONAL`, `CHINESE_TRADITION`. TikTok's sub-genres (`8_BIT`, `BOSSA_NOVA`, `CONTEMPORARY_R&B`, …) are passed through untouched. A value that cannot be an enum at all falls back to `ALL`. | `ALL` |
| `country_code` | string | No | ISO 3166-1 alpha-2 country code used to rank the tracks (e.g. `US`, `ES`, `GB`). | `US` |
| `date_range` | string | No | Ranking window: `1DAY`, `7DAY`, `30DAY`, `90DAY`. | `7DAY` |

### Example Request

```bash
curl -G 'https://api.upload-post.com/api/uploadposts/tiktok/music/trending' \
  -H 'Authorization: Apikey your-api-key-here' \
  -d 'profile=your_profile' \
  -d 'genre=POP' \
  -d 'country_code=ES' \
  -d 'date_range=7DAY'
```

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "genre": "POP",
  "country_code": "ES",
  "date_range": "7DAY",
  "tracks": [
    {
      "id": "7012345678901234567",
      "commercial_music_id": "6987654321098765432",
      "title": "Neon Skyline",
      "artist": "Wave Theory",
      "duration": 178,
      "rank": 1,
      "genres": ["POP", "ELECTRONIC"],
      "cover_url": "https://p16-sign.tiktokcdn.com/....jpeg",
      "preview_url": "https://sf16-cml.tiktokcdn.com/....mp3"
    },
    {
      "id": "7012345678901234568",
      "commercial_music_id": "6987654321098765433",
      "title": "Slow Coast",
      "artist": "Marina Vela",
      "duration": 142,
      "rank": 2,
      "genres": ["POP"],
      "cover_url": "https://p16-sign.tiktokcdn.com/....jpeg",
      "preview_url": "https://sf16-cml.tiktokcdn.com/....mp3"
    }
  ]
}
```

| Field | Description |
| :--- | :--- |
| `id` | **The value to pass as `tiktok_music_id`** on the upload request. |
| `commercial_music_id` | TikTok's catalogue id for the same track. Returned for reference only — publishing with it is rejected on public posts, always send `id`. |
| `title` / `artist` | Track name and performer. |
| `duration` | Track length in seconds. |
| `rank` | Position in the trending list for the requested `genre` / `country_code` / `date_range`. |
| `genres` | Genres TikTok assigns to the track. |
| `cover_url` | Artwork image, useful to render a picker in your own UI. |
| `preview_url` | Audio preview, for letting users listen before choosing. |

### Error Responses

- `400 Bad Request` — `profile` was not sent.

```json
{ "success": false, "message": "profile is required" }
```

- `404 Not Found` — no profile with that username exists under your API key.

```json
{
  "success": false,
  "message": "Profile 'your_profile' not found.",
  "error_code": "PROFILE_NOT_FOUND"
}
```

- `400 Bad Request` — the profile's TikTok connection does not support this
  endpoint (it lacks the `music` capability). Reconnect the account.

```json
{
  "success": false,
  "message": "Profile 'your_profile' has no TikTok connection that supports this endpoint. Reconnect your TikTok account from Manage Users.",
  "error_code": "tiktok_reconnect_required"
}
```

- `409 Conflict` — the TikTok token expired and the account must be reconnected
  (`"reauth_required": true`).
- `502 Bad Gateway` — TikTok rejected the query (for example an unknown `genre`).
  The upstream message is returned verbatim in `message`.

Invalid `genre` / `date_range` values are **not** a 400: unknown `date_range`
values fall back to `7DAY`, and a `genre` that cannot be an enum falls back to
`ALL`.

### Using a track on an upload

```bash
curl -X POST https://api.upload-post.com/api/upload \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user=your_profile' \
  -F 'platform[]=tiktok' \
  -F 'title=Golden hour 🌇' \
  -F 'video=@/path/to/video.mp4' \
  -F 'tiktok_music_id=7012345678901234567' \
  -F 'tiktok_music_volume=70' \
  -F 'tiktok_music_start=15000' \
  -F 'tiktok_music_end=45000' \
  -F 'tiktok_original_sound_volume=30'
```

See the full field reference in
[Upload Video → TikTok](./upload-video.md#tiktok).

### Additional Notes

- Volumes are independent: `tiktok_music_volume` controls the attached track and
  `tiktok_original_sound_volume` controls the audio embedded in your video. If you
  add music without setting the original volume, Upload-Post sends `50` so your
  own audio is not muted.
- `tiktok_music_start` / `tiktok_music_end` are offsets **in milliseconds** inside
  the track, used to pick which section plays.
- On **photo** posts only `tiktok_music_id` is honoured; the volume and trim
  fields are video-only. To let TikTok choose a track instead, use
  `auto_add_music`.


---
# Get TikTok Publishing Settings
URL: https://docs.upload-post.com/api/get-tiktok-settings

# Get TikTok Publishing Settings

Returns what a connected TikTok account is **allowed** to publish. Works for **standard and Business** TikTok connections.

The reason this endpoint exists is `privacy_level_options`. TikTok's enum has
four privacy values, but each account may only use a subset of them — a private
account, for example, has no `PUBLIC_TO_EVERYONE` at all. Sending one the
account does not have fails the upload with
`error_code: "tiktok_privacy_unavailable"`. Ask here first and offer only the
values that will work.

-   **Method:** `GET`
-   **Endpoint:** `/api/uploadposts/tiktok/settings`
-   **Authentication:**
    -   **API Key** in the `Authorization` header.
        -   `Authorization: Apikey <YOUR_API_KEY>`

### Query Parameters

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `profile` | string | Yes | The profile's `username`. Must have a TikTok account connected. |

### Example Request

```bash
curl -G 'https://api.upload-post.com/api/uploadposts/tiktok/settings' \
  -H 'Authorization: Apikey your-api-key-here' \
  -d 'profile=your_profile'
```

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "privacy_level_options": [
    "PUBLIC_TO_EVERYONE",
    "MUTUAL_FOLLOW_FRIENDS",
    "FOLLOWER_OF_CREATOR",
    "SELF_ONLY"
  ],
  "max_video_post_duration_sec": 600,
  "comment_disabled": false,
  "duet_disabled": false,
  "stitch_disabled": false
}
```

A **private** account answers with a narrower list and no public option:

```json
{
  "success": true,
  "privacy_level_options": [
    "FOLLOWER_OF_CREATOR",
    "MUTUAL_FOLLOW_FRIENDS",
    "SELF_ONLY"
  ],
  "max_video_post_duration_sec": 600,
  "comment_disabled": false,
  "duet_disabled": false,
  "stitch_disabled": false
}
```

| Field | Description |
| :--- | :--- |
| `privacy_level_options` | The privacy levels **this account** may publish with. Pass one of these as `privacy_level` on the upload. |
| `max_video_post_duration_sec` | Longest video the account can publish, in seconds. |
| `comment_disabled` | `true` when the account has comments turned off account-wide. Sending `disable_comment=false` will not re-enable them. |
| `duet_disabled` | Same, for duets. |
| `stitch_disabled` | Same, for stitches. |

### Error Responses

- `400 Bad Request` — missing `profile`.

```json
{ "success": false, "message": "profile is required" }
```

- `404 Not Found` — no profile with that username exists under your API key.

```json
{
  "success": false,
  "message": "Profile 'your_profile' not found.",
  "error_code": "PROFILE_NOT_FOUND"
}
```

- `400 Bad Request` — the profile's TikTok connection does not support this
  endpoint. Reconnect the account.

```json
{
  "success": false,
  "message": "Profile 'your_profile' has no TikTok connection that supports this endpoint. Reconnect your TikTok account from Manage Users.",
  "error_code": "tiktok_reconnect_required"
}
```

- `409 Conflict` — the TikTok token expired and the account must be reconnected
  (`"reauth_required": true`).
- `502 Bad Gateway` — TikTok rejected the lookup; the upstream message is
  returned verbatim in `message`.

### What happens if you skip this call

Nothing breaks silently. Asking for a privacy level the account cannot use is
refused **before** anything is sent to TikTok, and the error names the ones it
does have:

```json
{
  "success": false,
  "error": "Your TikTok account does not allow privacy_level='PUBLIC_TO_EVERYONE'. Allowed for this account: FOLLOWER_OF_CREATOR, MUTUAL_FOLLOW_FRIENDS, SELF_ONLY.",
  "error_code": "tiktok_privacy_unavailable"
}
```

That error is refundable: nothing was published, so the upload does not count
against your 24-hour allowance.

### Additional Notes

- On **video** uploads you may omit `privacy_level` entirely; TikTok then
  applies the account's own default. That is the only choice guaranteed to work
  on every account.
- On **photo** uploads TikTok requires a privacy level, so Upload-Post defaults
  to `PUBLIC_TO_EVERYONE` — which is exactly the value a private account cannot
  use. Read `privacy_level_options` and send one explicitly for those accounts.
- The answer is cached for a few minutes per account, so polling it while a user
  fills in a composer costs nothing upstream.
- `FOLLOWER_ONLY` is accepted on uploads as a legacy synonym of
  `FOLLOWER_OF_CREATOR`, but this endpoint always reports TikTok's own spelling.


---
# Pin a Google Business Location to a Profile
URL: https://docs.upload-post.com/api/google-business-location

Connecting Google Business links a **Google account**, which may manage several
business locations. Google's account chooser lets the user pick the account, not the
location, so a multi-location account has to send `gbp_location_id` on every upload —
and omitting it fails with *"gbp_location_id is required. Your account has N
locations — please select one."*

When a profile should always publish to one location — the usual case when each of
your customers or storefronts has its own profile — pin that location to the profile
once. From then on every upload from that profile (photos, text, video, scheduled
posts and retries) goes to the pinned location and you can omit `gbp_location_id`.

The same selection is available in the dashboard (**User Management → Google Business
card**) and in the white-label [connect page](./connect-api.md), so end users can pick
their location themselves right after connecting.

:::info Precedence
A pinned location **takes precedence** over the `gbp_location_id` parameter of the
upload endpoints, exactly like the [Facebook Page pin](./facebook-page.md) and the
[LinkedIn Page pin](./linkedin-page.md). To publish to another location from the same
profile, clear the pin first (`DELETE`) or use another profile.

Accounts with a single location keep working with no pin at all: it is auto-detected.
:::

- **Authentication:** any of
  - `Authorization: Apikey <YOUR_API_KEY>` (API key)
  - `Authorization: Bearer <user JWT>` (dashboard session)
  - the profile-scoped JWT issued for the connect page (see [User Profiles API](./user-profiles.md))

### **Get available locations and the current pin**

- **Method:** `GET`
- **Endpoint:** `/api/uploadposts/users/google-business-location`

| Parameter          | Type   | Description                                 | Required |
| :----------------- | :----- | :------------------------------------------ | :------- |
| `profile_username` | string | The profile's `username` (query parameter). | Yes      |

- **Successful Response (`200 OK`)**

```json
{
  "success": true,
  "locations": [
    {
      "name": "locations/1234567890",
      "title": "Acme Madrid",
      "account_id": "google-business-account-key",
      "account_name": "accounts/9876543210"
    }
  ],
  "selected_location_id": "locations/1234567890",
  "selected_location_name": "Acme Madrid"
}
```

`locations` is the same list returned by
[Get Google Business Locations](./get-google-business-locations.md) for that profile,
including locations that live under a secondary Google account (`account_name` is the
one that owns each location). `selected_location_id` / `selected_location_name` are
`null` when nothing is pinned. The same two fields are returned for each profile by
the [User Profiles API](./user-profiles.md) and by `validate-jwt`.

### **Pin a location**

- **Method:** `POST`
- **Endpoint:** `/api/uploadposts/users/google-business-location`
- **Body (JSON):**

| Field              | Type   | Description                                                                                                                | Required |
| :----------------- | :----- | :------------------------------------------------------------------------------------------------------------------------- | :------- |
| `profile_username` | string | The profile's `username`.                                                                                                  | Yes      |
| `gbp_location_id`  | string | Location `name` from the list (`locations/1234567890`), the bare id (`1234567890`) or the full path (`accounts/98/locations/12`). Must belong to the account connected to this profile. | Yes |

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/google-business-location \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"profile_username": "acme", "gbp_location_id": "locations/1234567890"}'
```

- **Successful Response (`200 OK`)**

```json
{
  "success": true,
  "gbp_location_id": "locations/1234567890",
  "gbp_location_name": "Acme Madrid"
}
```

The location title and its owning Google account are taken from Google, never from
the request. A `gbp_location_id` the connected account cannot post to returns `400`.

### **Clear the pin**

- **Method:** `DELETE`
- **Endpoint:** `/api/uploadposts/users/google-business-location`
- **Body (JSON):** `{"profile_username": "acme"}`

After clearing, uploads fall back to the default behaviour: the `gbp_location_id`
parameter, or auto-detection when the account has exactly one location.

### **Error Responses**

| Status | Meaning                                                                                                                                          |
| :----- | :------------------------------------------------------------------------------------------------------------------------------------------------ |
| `400`  | Missing `profile_username` / `gbp_location_id`, profile has no Google Business connected, token unavailable, or the location is not available to that account. |
| `404`  | Profile not found.                                                                                                                                |


---
# Google Business Reviews
URL: https://docs.upload-post.com/api/google-business-reviews

Read and reply to reviews on a connected Google Business Profile location. Both endpoints call the Google Business Profile API in real-time and refresh the stored access token automatically. Connect the Google account via OAuth first (the same connection used for Google Business posting — no extra permission is required).

## List Reviews

-   **Method:** `GET`
-   **Endpoint:** `/api/uploadposts/google-business/reviews`
-   **Authentication:** API Key — `Authorization: Apikey <YOUR_API_KEY>`

### Query Parameters

| Parameter     | Type    | Required | Description                                                                                             |
| :------------ | :------ | :------- | :------------------------------------------------------------------------------------------------------ |
| `user`        | string  | Yes      | The profile `username` that owns the connected Google Business account.                                  |
| `location_id` | string  | No       | Location to read, e.g. `locations/222222222` or a full `accounts/.../locations/...`. Defaults to the account's selected location. |
| `pageSize`    | integer | No       | Max reviews per page.                                                                                    |
| `pageToken`   | string  | No       | Pagination cursor from a previous response (`nextPageToken`).                                            |
| `orderBy`     | string  | No       | Sort order, e.g. `updateTime desc` or `rating desc`.                                                     |

### Example Request

```bash
curl 'https://api.upload-post.com/api/uploadposts/google-business/reviews?user=my-profile&pageSize=20' \
  -H 'Authorization: Apikey YOUR_API_KEY'
```

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "reviews": [
    {
      "name": "accounts/123456789/locations/222222222/reviews/AbcDef",
      "reviewer": { "displayName": "Jane Doe" },
      "starRating": "FIVE",
      "comment": "Great service!",
      "createTime": "2026-07-01T10:00:00Z",
      "updateTime": "2026-07-01T10:00:00Z",
      "reviewReply": { "comment": "Thanks Jane!", "updateTime": "2026-07-02T09:00:00Z" }
    }
  ],
  "averageRating": 4.7,
  "totalReviewCount": 128,
  "nextPageToken": "..."
}
```

| Field              | Description                                                        |
| :----------------- | :----------------------------------------------------------------- |
| `reviews`          | Array of reviews. Each review's `name` is the full resource path used to reply. |
| `averageRating`    | Average star rating for the location.                              |
| `totalReviewCount` | Total number of reviews for the location.                          |
| `nextPageToken`    | Present when more reviews are available; pass it as `pageToken`.   |

---

## Reply to a Review

Creates the owner reply to a review, or updates it if one already exists.

-   **Method:** `PUT` (also accepts `POST`)
-   **Endpoint:** `/api/uploadposts/google-business/reviews/reply`
-   **Authentication:** API Key — `Authorization: Apikey <YOUR_API_KEY>`

### Body Parameters (JSON)

| Name          | Type   | Required | Description                                                                                          |
| :------------ | :----- | :------- | :--------------------------------------------------------------------------------------------------- |
| `user`        | String | Yes      | The profile `username` that owns the connected Google Business account.                              |
| `comment`     | String | Yes      | The public reply text.                                                                               |
| `review_name` | String | No\*     | Full review resource path from **List Reviews** (`accounts/.../locations/.../reviews/{id}`).          |
| `review_id`   | String | No\*     | Review ID. Requires `location_id` to build the resource path.                                        |
| `location_id` | String | No       | Location for the review when using `review_id`.                                                       |

\* Provide either `review_name` (preferred) or `review_id` + `location_id`.

### Example Request

```bash
curl -X PUT https://api.upload-post.com/api/uploadposts/google-business/reviews/reply \
  -H 'Authorization: Apikey YOUR_API_KEY' \
  -H 'Content-Type: application/json' \
  -d '{
    "user": "my-profile",
    "review_name": "accounts/123456789/locations/222222222/reviews/AbcDef",
    "comment": "Thank you for the kind words — see you again soon!"
  }'
```

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "reply": { "comment": "Thank you for the kind words — see you again soon!", "updateTime": "2026-07-18T12:00:00Z" }
}
```

> **Notes:**
> - Reviews and replies use the Google Business Profile v4 API. Only **verified** locations can receive replies.
> - Reviews cannot be deleted via the API, and star ratings cannot be changed — you can only reply.
> - Token refresh is handled automatically; connect the Google account via OAuth first.

---

## Delete a Review Reply

-   **Method:** `DELETE`
-   **Endpoint:** `/api/uploadposts/google-business/reviews/reply`

Same body identifiers as **Reply to a Review** (`user` + `review_name`, or `review_id` + `location_id`).

---

## Batch Get Reviews

-   **Method:** `GET` or `POST`
-   **Endpoint:** `/api/uploadposts/google-business/reviews/batch`

Fetches reviews for multiple locations in one call (`locations:batchGetReviews`).


---
# Comments
URL: https://docs.upload-post.com/api/instagram-comments

# Comments

Retrieve comments from social media posts, send private replies (DMs) to commenters, or post public replies visible under the original comment.

---

## Get Post Comments

Retrieve all comments on a specific post. Accepts either a numeric media ID or a full post URL.

### Endpoint

```
GET /api/uploadposts/comments
```

### Headers

| Name          | Value                    | Description                     |
|---------------|--------------------------|---------------------------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Query Parameters

| Name     | Type    | Required | Description                                                                                                                    |
|----------|---------|----------|--------------------------------------------------------------------------------------------------------------------------------|
| platform | String  | Yes      | The platform to retrieve comments from (e.g., `"instagram"`).                                                                  |
| user     | String  | Yes      | Profile username (as configured in Upload-Post).                                                                               |
| post_id  | String  | Yes\*    | Numeric media ID. Use `post_id` or `post_url` (one is required).                                                               |
| post_url | String  | Yes\*    | Full post URL (e.g., `https://www.instagram.com/p/ABC123/`). Alternative to `post_id`.                                         |
| limit    | Integer | No       | Comments per page, between **1 and 50** (Meta's hard cap for this edge). If omitted, Instagram's default applies (~25).        |
| after    | String  | No       | Cursor returned by Meta in the previous page (`pagination.next_cursor`). Use it to fetch the next page.                        |

> **Ordering:** Meta returns comments newest-first (reverse-chronological) on Graph API v3.2 and later. There is no query parameter to change this — the order is fixed by Meta.

### Example Requests

**Single page (default):**

```bash
curl 'https://api.upload-post.com/api/uploadposts/comments?platform=instagram&user=my-profile&post_url=https://www.instagram.com/p/ABC123/' \
  -H 'Authorization: Apikey your-api-key-here'
```

**Paginate through all comments (50 per page):**

```bash
# First page
curl 'https://api.upload-post.com/api/uploadposts/comments?platform=instagram&user=my-profile&post_id=17890455123456789&limit=50' \
  -H 'Authorization: Apikey your-api-key-here'

# Next page — pass back the next_cursor from the previous response
curl 'https://api.upload-post.com/api/uploadposts/comments?platform=instagram&user=my-profile&post_id=17890455123456789&limit=50&after=QVFIUm...' \
  -H 'Authorization: Apikey your-api-key-here'
```

Loop while `pagination.has_next` is `true`, passing `pagination.next_cursor` as `after` each time.

### Responses

- **200 OK**

```json
{
  "success": true,
  "comments": [
    {
      "id": "17858893269123456",
      "text": "Great post!",
      "timestamp": "2025-06-15T10:30:00+0000",
      "user": {
        "id": "17841400123456789",
        "username": "commenter_user"
      }
    },
    {
      "id": "17858893269789012",
      "text": "Love this content",
      "timestamp": "2025-06-15T11:00:00+0000",
      "user": {
        "id": "17841400987654321",
        "username": "another_user"
      }
    }
  ],
  "pagination": {
    "next_cursor": "QVFIUm9TbGd...",
    "has_next": true
  }
}
```

When the last page is reached, `pagination` is `{"next_cursor": null, "has_next": false}`.

- **400 Bad Request**

```json
{
  "success": false,
  "error": "Missing required query parameters: platform, user, and either post_id or post_url"
}
```

- **400 Bad Request** (invalid `limit`)

```json
{
  "success": false,
  "error": "Query parameter 'limit' must be between 1 and 50 (Meta's cap for this edge)."
}
```

- **400 Bad Request** (invalid post URL)

```json
{
  "success": false,
  "error": "Could not find media ID for the provided URL. Make sure the post belongs to the authenticated account."
}
```

- **500 Internal Server Error**

```json
{
  "success": false,
  "error": "An internal server error occurred."
}
```

A post URL is resolved to a media ID by scanning the account's recent posts (cached). The post must belong to the authenticated account. This endpoint uses the account's [global API rate limits](../guides/rate-limits.md); there is no per-post throttle.

---

## Reply to Comment (Private Reply)

Send a private reply (DM) to the author of a comment on your post. This sends a direct message to the commenter.

### Endpoint

```
POST /api/uploadposts/comments/reply
```

### Headers

| Name          | Value                    | Description                     |
|---------------|--------------------------|---------------------------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Body Parameters (JSON)

| Name       | Type   | Required | Description                                                  |
|------------|--------|----------|--------------------------------------------------------------|
| platform   | String | Yes      | The platform (e.g., `"instagram"`).                          |
| user       | String | Yes      | Profile username (as configured in Upload-Post).             |
| comment_id | String | Yes      | The ID of the comment to reply to (from Get Post Comments).  |
| message    | String | Yes      | The private reply message text.                              |
| buttons    | Array  | No       | Up to 3 `web_url` buttons rendered in the Instagram DM. Each item is an object `{title, url}` (title max 20 chars, url must be http/https). |

### Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/comments/reply \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "instagram",
    "user": "my-profile",
    "comment_id": "17858893269123456",
    "message": "Thanks for your comment! Check your DMs for more info.",
    "buttons": [
      { "title": "Learn more", "url": "https://example.com" }
    ]
  }'
```

### Responses

- **200 OK** (reply sent successfully)

```json
{
  "success": true,
  "recipient_id": "17841400123456789",
  "message_id": "aWdGM...",
  "message": "Private reply sent successfully"
}
```

- **400 Bad Request** (missing fields)

```json
{
  "success": false,
  "error": "Missing required fields: platform, user, comment_id, message"
}
```

- **429 Too Many Requests** (daily DM limit exceeded)

```json
{
  "success": false,
  "error": "Daily DM limit exceeded."
}
```

- **500 Internal Server Error**

```json
{
  "success": false,
  "error": "An internal server error occurred."
}
```

---

## Reply to Comment (Public Reply)

Post a public reply to a comment on your Instagram post. The reply appears as a visible comment under the original comment.

### Endpoint

```
POST /api/uploadposts/comments/public-reply
```

### Headers

| Name          | Value                    | Description                     |
|---------------|--------------------------|---------------------------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Body Parameters (JSON)

| Name       | Type   | Required | Description                                                  |
|------------|--------|----------|--------------------------------------------------------------|
| platform   | String | Yes      | The platform (e.g., `"instagram"`).                          |
| user       | String | Yes      | Profile username (as configured in Upload-Post).             |
| comment_id | String | Yes      | The ID of the comment to reply to (from Get Post Comments).  |
| message    | String | Yes      | The public reply message text.                               |

### Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/comments/public-reply \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "instagram",
    "user": "my-profile",
    "comment_id": "17858893269123456",
    "message": "Thank you! Glad you liked it."
  }'
```

### Responses

- **200 OK** (reply posted successfully)

```json
{
  "success": true,
  "id": "17858893269654321",
  "message": "Instagram public reply posted successfully"
}
```

- **400 Bad Request** (missing fields)

```json
{
  "success": false,
  "error": "Missing required fields: platform, user, comment_id, message"
}
```

- **429 Too Many Requests** (daily limit exceeded)

```json
{
  "success": false,
  "error": "Daily DM limit exceeded."
}
```

- **500 Internal Server Error**

```json
{
  "success": false,
  "error": "An internal server error occurred."
}
```

---

## Important Notes

1. **7-day window for private replies**: Some platforms only allow private replies to recent comments (e.g., Instagram requires comments less than 7 days old). Public replies do not have this restriction.

2. **Comment must be on your post**: You can only reply to comments on posts owned by the authenticated account.

3. **Daily limits**: Upload-Post enforces a configurable daily limit per user. Both private and public replies count toward this limit. When exceeded, the API returns a `429` status code.

4. **Public vs. private replies**: Use `/comments/reply` to send a private DM to the comment author. Use `/comments/public-reply` to post a visible reply under the original comment. Both require the `comment_id` from the Get Post Comments endpoint.

5. **Using comment data for DMs**: Each comment includes the commenter's user ID. You can use this ID with the [Direct Messages](./instagram-dms.md) endpoint to send follow-up DMs directly.


---
# Direct Messages
URL: https://docs.upload-post.com/api/instagram-dms

# Direct Messages

Send direct messages (DMs) and retrieve conversations on connected social media accounts.

---

## Send a Direct Message

Send a DM directly to a user using their platform-specific User ID.

### Endpoint

```
POST /api/uploadposts/dms/send
```

### Headers

| Name          | Value                    | Description                     |
|---------------|--------------------------|---------------------------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Body Parameters (JSON)

| Name         | Type   | Required | Description                                                                 |
|--------------|--------|----------|-----------------------------------------------------------------------------|
| platform     | String | Yes      | The platform to send the DM on (e.g., `"instagram"`).                      |
| user         | String | Yes      | Profile username (as configured in Upload-Post).                            |
| recipient_id | String | Yes      | The platform-specific User ID of the recipient.                            |
| message      | String | Yes      | The text message to send.                                                   |
| buttons      | Array  | No       | Up to 3 `web_url` buttons rendered in the Instagram DM. Each item is an object `{title, url}` (title max 20 chars, url must be http/https). |
| attachments / attachment | Object or array | No | `{type, url}` or `{type, attachment_id}`. Up to 10 images. Types: `image`, `video`, `audio`, `file`, `like_heart`, `media_share`. |
| quick_replies | Array | No | Up to 13 options; title max 20 characters. |
| generic_template | Object | No | Up to 10 elements; title max 80 characters; 3 buttons. |
| reply_to | String | No | Message id (`mid`) to reply to. |
| sender_action | String | No | `typing_on`, `typing_off`, `mark_seen`, `react`, `unreact`. Does not consume the daily DM quota. |

### Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/dms/send \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "instagram",
    "user": "my-profile",
    "recipient_id": "17841400123456789",
    "message": "Hello! Thanks for reaching out.",
    "buttons": [
      { "title": "Visit site", "url": "https://example.com" }
    ]
  }'
```

### Responses

- **200 OK** (DM sent successfully)

```json
{
  "success": true,
  "recipient_id": "17841400123456789",
  "message_id": "aWdGM...",
  "message": "DM sent successfully"
}
```

- **400 Bad Request** (missing fields, invalid account)

```json
{
  "success": false,
  "error": "Missing required fields: platform, user, recipient_id, message"
}
```

- **429 Too Many Requests** (daily DM limit exceeded)

```json
{
  "success": false,
  "error": "Daily DM limit exceeded."
}
```

- **500 Internal Server Error**

```json
{
  "success": false,
  "error": "An internal server error occurred."
}
```

---

## Get Conversations

Retrieve the list of DM conversations for an account, including participants and recent messages.

### Endpoint

```
GET /api/uploadposts/dms/conversations
```

### Headers

| Name          | Value                    | Description                     |
|---------------|--------------------------|---------------------------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Query Parameters

| Name     | Type   | Required | Description                                                      |
|----------|--------|----------|------------------------------------------------------------------|
| platform | String | Yes      | The platform to retrieve conversations from (e.g., `"instagram"`). |
| user     | String | Yes      | Profile username (as configured in Upload-Post).                 |

### Example Request

```bash
curl 'https://api.upload-post.com/api/uploadposts/dms/conversations?platform=instagram&user=my-profile' \
  -H 'Authorization: Apikey your-api-key-here'
```

### Responses

- **200 OK**

```json
{
  "success": true,
  "conversations": [
    {
      "id": "t_123456789",
      "participants": {
        "data": [
          { "id": "17841400123456789", "username": "user_one" },
          { "id": "17841400987654321", "username": "user_two" }
        ]
      },
      "messages": {
        "data": [
          {
            "id": "aWdGM...",
            "created_time": "2025-01-15T10:30:00+0000",
            "from": { "id": "17841400123456789", "username": "user_one" },
            "to": { "data": [{ "id": "17841400987654321", "username": "user_two" }] },
            "message": "Hey, thanks for the info!"
          }
        ]
      }
    }
  ]
}
```

- **400 Bad Request**

```json
{
  "success": false,
  "error": "Missing required query parameters: platform, user"
}
```

- **500 Internal Server Error**

```json
{
  "success": false,
  "error": "An internal server error occurred."
}
```

---

## Important Notes

1. **Messaging policies vary by platform**: Each platform has its own messaging rules. For example, Instagram requires the recipient to have messaged your account first (24-hour window).

2. **Daily DM limits**: Upload-Post enforces a configurable daily DM limit per user to prevent accidental overuse. When the limit is reached, the API returns a `429` status code.

### Where to find the recipient ID

The recipient's User ID can be obtained from:

- The **Get Conversations** endpoint above (each participant has an `id` field).
- The **Get Post Comments** endpoint (`GET /api/uploadposts/comments`) where each comment includes the commenter's `user.id`.

### Difference from Comment Replies

| Feature | Comment Reply (`/comments/reply`) | Direct Message (`/dms/send`) |
|---|---|---|
| Recipient | `comment_id` — replies to a specific comment | `recipient_id` — sends to a user by ID |
| Use case | Auto-reply to post engagement | Customer support, follow-up conversations |


---
# Media List
URL: https://docs.upload-post.com/api/instagram-media

# Media List

Retrieve a list of recent media (posts, reels, videos, pins, tweets, etc.) from a connected social media account. Supports all major platforms: Instagram, TikTok, YouTube, LinkedIn, Facebook, X (Twitter), Threads, Pinterest, Bluesky, and Reddit.

Useful for building post selectors, displaying recent content, or getting media IDs for other API calls.

---

## Get User Media

### Endpoint

```
GET /api/uploadposts/media
```

### Headers

| Name          | Value                    | Description                     |
|---------------|--------------------------|---------------------------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Query Parameters

| Name     | Type   | Required | Description                                                      |
|----------|--------|----------|------------------------------------------------------------------|
| platform | String | Yes      | The platform to retrieve media from. See [Supported Platforms](#supported-platforms). |
| user     | String | Yes      | Profile username (as configured in Upload-Post).                 |
| limit    | Integer | No      | Number of media items to return. Defaults to `25`, clamped to the range `1`–`100`, and further capped per platform. See [Pagination](#pagination). |
| cursor   | String | No       | Opaque next-page token returned by the previous response (`pagination.next_cursor`). Not supported on LinkedIn, Discord and Telegram. See [Pagination](#pagination). |
| page_urn | String | No       | **LinkedIn only.** Selects which LinkedIn **organization page** to fetch posts from. Accepts a numeric organization ID (e.g., `12345`) or a full URN (e.g., `urn:li:organization:12345`). If omitted, the first organization the connected account administers is auto-resolved. Personal profiles are **not** supported — see the note below. Use the [LinkedIn Pages](/api/get-linkedin-pages) endpoint to list available organizations. |

### Supported Platforms

| Platform    | Value        | Description                                |
|-------------|--------------|--------------------------------------------|
| Instagram   | `instagram`  | Posts, reels, and carousels                |
| TikTok      | `tiktok`     | Videos                                     |
| YouTube     | `youtube`    | Videos from the channel's uploads playlist |
| LinkedIn    | `linkedin`   | Posts (text, images, articles)             |
| Facebook    | `facebook`   | Page/profile posts                         |
| X (Twitter) | `x`          | Tweets with media information              |
| Threads     | `threads`    | Thread posts                               |
| Pinterest   | `pinterest`  | Pins                                       |
| Bluesky     | `bluesky`    | Posts (skeets)                             |
| Reddit      | `reddit`     | Submissions                                |

### Example Requests

**Instagram:**
```bash
curl 'https://api.upload-post.com/api/uploadposts/media?platform=instagram&user=my-profile' \
  -H 'Authorization: Apikey your-api-key-here'
```

**TikTok:**
```bash
curl 'https://api.upload-post.com/api/uploadposts/media?platform=tiktok&user=my-profile' \
  -H 'Authorization: Apikey your-api-key-here'
```

**YouTube:**
```bash
curl 'https://api.upload-post.com/api/uploadposts/media?platform=youtube&user=my-profile' \
  -H 'Authorization: Apikey your-api-key-here'
```

**LinkedIn (personal profile):**
```bash
curl 'https://api.upload-post.com/api/uploadposts/media?platform=linkedin&user=my-profile' \
  -H 'Authorization: Apikey your-api-key-here'
```

**LinkedIn (organization page):**
```bash
curl 'https://api.upload-post.com/api/uploadposts/media?platform=linkedin&user=my-profile&page_urn=12345' \
  -H 'Authorization: Apikey your-api-key-here'
```

**LinkedIn (a specific organization page you administer):**
```bash
curl 'https://api.upload-post.com/api/uploadposts/media?platform=linkedin&user=my-profile&page_urn=12345' \
  -H 'Authorization: Apikey your-api-key-here'
```

:::warning LinkedIn lists organization posts only
LinkedIn does not let third-party apps list the posts a **person** authored:
that needs its restricted `r_member_social` permission, granted to approved
partners only. Requesting a personal profile — `page_urn=me`, or an account
connected without any administered page — returns `400` with an explanation
rather than a partial or misleading list. Posts published to a **company page**
you administer are listed normally.

If you need the comments on a personal post, you do not need this endpoint:
pass the post URN straight to [Comments](/api/comments) as `post_id`.
:::

### Responses

- **200 OK**

```json
{
  "success": true,
  "media": [
    {
      "id": "17890455123456789",
      "caption": "Check out our latest product launch!",
      "media_type": "IMAGE",
      "media_url": "https://scontent.xx.fbcdn.net/v/image123.jpg",
      "permalink": "https://www.instagram.com/p/ABC123/",
      "timestamp": "2025-06-15T10:30:00+0000",
      "thumbnail_url": null
    },
    {
      "id": "17890455987654321",
      "caption": "Behind the scenes",
      "media_type": "VIDEO",
      "media_url": "https://scontent.xx.fbcdn.net/v/video456.mp4",
      "permalink": "https://www.instagram.com/reel/DEF456/",
      "timestamp": "2025-06-14T15:00:00+0000",
      "thumbnail_url": "https://scontent.xx.fbcdn.net/..."
    },
    {
      "id": "17890455111222333",
      "caption": "Photo dump",
      "media_type": "CAROUSEL_ALBUM",
      "media_url": null,
      "permalink": "https://www.instagram.com/p/GHI789/",
      "timestamp": "2025-06-13T09:00:00+0000",
      "thumbnail_url": null
    }
  ],
  "pagination": {
    "limit": 50,
    "next_cursor": "QVFIUkc...",
    "has_more": true
  }
}
```

The `pagination` object is additive — every pre-existing key of the response (`success`, `media`, and the per-item fields) is unchanged, so existing integrations keep working without any modification.

- **400 Bad Request**

```json
{
  "success": false,
  "error": "Platform parameter is required"
}
```

```json
{
  "success": false,
  "error": "Pagination cursor is not supported for LinkedIn"
}
```

- **500 Internal Server Error**

```json
{
  "success": false,
  "error": "An internal server error occurred."
}
```

### Response Fields

All platforms return media items with a consistent structure:

| Field           | Type        | Description                                              |
|-----------------|-------------|----------------------------------------------------------|
| `id`            | String      | Platform-specific unique identifier for the media item   |
| `caption`       | String      | Text content, caption, or title of the post              |
| `media_type`    | String      | Type of media. See [Media Types](#media-types)           |
| `media_url`     | String/null | Direct URL to the media file (image or video). See [Media URL Availability](#media-url-availability) |
| `permalink`     | String/null | Direct URL to the post on the platform                   |
| `timestamp`     | String/null | ISO 8601 timestamp of when the post was created          |
| `thumbnail_url` | String/null | URL of the thumbnail/preview image (if available)        |

| Field        | Type    | Description                                                                 |
|--------------|---------|-----------------------------------------------------------------------------|
| `pagination` | Object  | Page metadata. See [Pagination](#pagination).                               |

### Pagination

Use `limit` and `cursor` to page through an account's media instead of receiving only the most recent items.

| Name     | Type    | Default | Description                                                                                       |
|----------|---------|---------|---------------------------------------------------------------------------------------------------|
| `limit`  | Integer | `25`    | How many items to return. Values are clamped to `1`–`100`, then capped again by the platform's own maximum page size (see the table below). |
| `cursor` | String  | –       | Opaque token identifying the next page. Pass back exactly what the previous response returned in `pagination.next_cursor`. Do not build, parse or store these tokens — they are the platform's own paging tokens and their format can change at any time. |

Every response carries a `pagination` object:

```json
{
  "limit": 50,
  "next_cursor": "QVFIUkc...",
  "has_more": true
}
```

| Field         | Type        | Description                                                                 |
|---------------|-------------|-----------------------------------------------------------------------------|
| `limit`       | Integer     | The effective limit that was applied after clamping and per-platform capping. |
| `next_cursor` | String/null | Token to pass as `cursor` on the next request. `null` when there are no more pages. |
| `has_more`    | Boolean     | `true` when another page is available.                                       |

On the last page the object looks like this:

```json
{
  "limit": 50,
  "next_cursor": null,
  "has_more": false
}
```

#### Per-Platform Limits and Cursor Support

| Platform    | Max `limit` | Cursor support | Notes                                              |
|-------------|-------------|----------------|----------------------------------------------------|
| Instagram   | 100         | Yes            | Graph API `after` cursor                           |
| Facebook    | 100         | Yes            | Graph API `after` cursor                           |
| Threads     | 100         | Yes            | Graph API `after` cursor                           |
| TikTok      | 20          | Yes            | TikTok caps its page size at 20 items              |
| YouTube     | 50          | Yes            | YouTube caps its page size at 50 items             |
| Bluesky     | 100         | Yes            |                                                    |
| X (Twitter) | 100         | Yes            |                                                    |
| Pinterest   | 100         | Yes            |                                                    |
| Reddit      | 100         | Yes            |                                                    |
| LinkedIn    | 100         | **No**         | `limit` only. Passing `cursor` returns `400`.      |
| Discord     | –           | **No**         | `limit` only. Passing `cursor` returns `400`.      |
| Telegram    | –           | **No**         | `limit` only. Passing `cursor` returns `400`.      |

:::warning
**LinkedIn, Discord and Telegram do not support cursors.** Their APIs do not expose a paging token, so these platforms honour `limit` only. Sending a `cursor` for one of them returns `400 Bad Request` with a message naming the platform (e.g. `"Pagination cursor is not supported for LinkedIn"`), rather than silently ignoring it.
:::

#### Requesting a Specific Page Size

```bash
curl 'https://api.upload-post.com/api/uploadposts/media?platform=instagram&user=my-profile&limit=50' \
  -H 'Authorization: Apikey your-api-key-here'
```

#### Requesting the Next Page

```bash
curl 'https://api.upload-post.com/api/uploadposts/media?platform=instagram&user=my-profile&limit=50&cursor=QVFIUkc...' \
  -H 'Authorization: Apikey your-api-key-here'
```

#### Paginating Through Everything

Keep calling the endpoint with the `next_cursor` from the previous response until `has_more` is `false`.

<Tabs groupId="lang">
<TabItem value="curl" label="cURL">

```bash
API_KEY="your-api-key-here"
CURSOR=""

while :; do
  URL="https://api.upload-post.com/api/uploadposts/media?platform=instagram&user=my-profile&limit=100"
  [ -n "$CURSOR" ] && URL="$URL&cursor=$CURSOR"

  RESPONSE=$(curl -s "$URL" -H "Authorization: Apikey $API_KEY")
  echo "$RESPONSE" | jq -r '.media[].id'

  [ "$(echo "$RESPONSE" | jq -r '.pagination.has_more')" = "true" ] || break
  CURSOR=$(echo "$RESPONSE" | jq -r '.pagination.next_cursor')
done
```

</TabItem>
<TabItem value="python" label="Python">

```python
import requests

API_KEY = "your-api-key-here"
all_media = []
cursor = None

while True:
    params = {"platform": "instagram", "user": "my-profile", "limit": 100}
    if cursor:
        params["cursor"] = cursor

    response = requests.get(
        "https://api.upload-post.com/api/uploadposts/media",
        headers={"Authorization": f"Apikey {API_KEY}"},
        params=params,
    )
    data = response.json()
    all_media.extend(data["media"])

    pagination = data.get("pagination", {})
    if not pagination.get("has_more"):
        break
    cursor = pagination["next_cursor"]

print(f"Fetched {len(all_media)} media items")
```

</TabItem>
<TabItem value="js" label="JavaScript">

```javascript
const API_KEY = "your-api-key-here";
const allMedia = [];
let cursor = null;

while (true) {
  const params = new URLSearchParams({
    platform: "instagram",
    user: "my-profile",
    limit: "100",
  });
  if (cursor) params.set("cursor", cursor);

  const response = await fetch(
    `https://api.upload-post.com/api/uploadposts/media?${params}`,
    { headers: { Authorization: `Apikey ${API_KEY}` } }
  );
  const data = await response.json();
  allMedia.push(...data.media);

  if (!data.pagination?.has_more) break;
  cursor = data.pagination.next_cursor;
}

console.log(`Fetched ${allMedia.length} media items`);
```

</TabItem>
</Tabs>

:::tip
For LinkedIn, Discord and Telegram there is no second page: request the largest `limit` you need in a single call and stop.
:::

### Media Types

| Type             | Description                                    |
|------------------|------------------------------------------------|
| `IMAGE`          | Single photo post or image pin                 |
| `VIDEO`          | Video post, reel, or video pin                 |
| `CAROUSEL_ALBUM` | Multi-image/video post (carousel)              |
| `TEXT`           | Text-only post (no media attached)             |

### Media URL Availability

The `media_url` field returns a direct URL to the media file (image or video) when available. Support varies by platform:

| Platform    | `media_url` Support | Details                                                  |
|-------------|---------------------|----------------------------------------------------------|
| Instagram   | Yes                 | Direct image/video URL. Not available for CAROUSEL_ALBUM parent (use children). May be omitted for copyrighted content. URLs are temporary. |
| Threads     | Yes                 | Direct image/video URL, same behavior as Instagram.       |
| Facebook    | Yes                 | Image URL or playable video URL via attachments.          |
| X (Twitter) | Yes                 | Direct photo URL. For videos, returns the preview image URL. |
| LinkedIn    | Yes                 | Resolved via Images/Videos API. URLs are signed and temporary. |
| Reddit      | Yes                 | Direct `i.redd.it` image URL, `v.redd.it` video URL (video-only, no audio), or first gallery image URL. |
| Bluesky     | Yes                 | `fullsize` CDN image (up to 2000px) or HLS playlist URL (`.m3u8`) for videos. |
| Pinterest   | Yes                 | Largest available image URL (up to 1200px or original). Video URLs are restricted by Pinterest. |
| TikTok      | No                  | TikTok API does not expose direct video file URLs. Use `permalink` instead. |
| YouTube     | No                  | YouTube API does not provide direct video URLs (prohibited by ToS). Use `permalink` instead. |

:::warning
Media URLs from most platforms are **temporary** and will expire after some time (hours to days). Do not store them permanently — re-fetch from the API when needed, or download the media file to your own storage.
:::

### Common Use Cases

- **Post selector UI**: Display the user's recent posts so they can pick one for comment monitoring or AutoDMs.
- **Get media IDs**: Use the `id` field from the response as the `post_id` parameter in the [Comments](./instagram-comments.md) endpoint.
- **Content overview**: Show a dashboard of recent content across all platforms with permalinks and captions.
- **Cross-platform analytics**: Aggregate media from multiple platforms to display a unified content calendar.
- **Full back-catalogue sync**: Use [`limit` and `cursor`](#pagination) to walk an account's entire media history, then feed the resulting IDs into [Cached Post Analytics](./get-analytics.md#get-apiuploadpostspost-analyticscached) for bulk metrics.


---
# Pin a LinkedIn Page to a Profile
URL: https://docs.upload-post.com/api/linkedin-page

Connecting LinkedIn links a **member account**, which may administer several company
Pages. LinkedIn shows no chooser during the OAuth flow, so by default every upload
goes to the personal profile unless `target_linkedin_page_id` is sent. When a profile
should always post as one company Page — the usual case when each of your customers
or brands has its own profile — pin that Page to the profile once. From then on every
upload from that profile (video, photos, text, documents, scheduled posts and retries)
is published as the pinned Page and you can omit `target_linkedin_page_id`.

The same selection is available in the dashboard (**User Management → LinkedIn
card**) and in the white-label [connect page](./connect-api.md), so end users can
pick their Page themselves right after connecting.

:::info Precedence
A pinned Page **takes precedence** over the `target_linkedin_page_id` parameter of
the upload endpoints, exactly like the [Facebook Page pin](./facebook-page.md). To
post to the personal profile or another Page from the same profile, clear the pin
first (`DELETE`) or use another profile.
:::

- **Authentication:** any of
  - `Authorization: Apikey <YOUR_API_KEY>` (API key)
  - `Authorization: Bearer <user JWT>` (dashboard session)
  - the profile-scoped JWT issued for the connect page (see [User Profiles API](./user-profiles.md))

### **Get available Pages and the current pin**

- **Method:** `GET`
- **Endpoint:** `/api/uploadposts/users/linkedin-page`

| Parameter          | Type   | Description                                  | Required |
| :----------------- | :----- | :------------------------------------------- | :------- |
| `profile_username` | string | The profile's `username` (query parameter). | Yes      |

- **Successful Response (`200 OK`)**

```json
{
  "success": true,
  "pages": [
    {
      "id": "urn:li:organization:123456",
      "name": "Acme Inc",
      "picture": "https://media.licdn.com/...",
      "vanityName": "acme-inc",
      "followers": 1480,
      "account_id": "linkedin-account-key"
    }
  ],
  "selected_page_id": "urn:li:organization:123456",
  "selected_page_name": "Acme Inc"
}
```

`pages` is the same list returned by [Get LinkedIn Pages](./get-linkedin-pages.md) for
that profile. `selected_page_id` / `selected_page_name` are `null` when no Page is
pinned (posts go to the personal profile). The same two fields are returned for
each profile by the [User Profiles API](./user-profiles.md) and by `validate-jwt`.

### **Pin a Page**

- **Method:** `POST`
- **Endpoint:** `/api/uploadposts/users/linkedin-page`
- **Body (JSON):**

| Field              | Type   | Description                                                                                          | Required |
| :----------------- | :----- | :--------------------------------------------------------------------------------------------------- | :------- |
| `profile_username` | string | The profile's `username`.                                                                            | Yes      |
| `linkedin_page_id` | string | Page `id` from the `pages` list (`urn:li:organization:123456`) or just the numeric id (`123456`). Must be administered by the account connected to this profile. | Yes |

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/linkedin-page \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"profile_username": "acme", "linkedin_page_id": "urn:li:organization:123456"}'
```

- **Successful Response (`200 OK`)**

```json
{
  "success": true,
  "linkedin_page_id": "urn:li:organization:123456",
  "linkedin_page_name": "Acme Inc"
}
```

The Page name is taken from LinkedIn, never from the request. A `linkedin_page_id`
that the connected account does not administer returns `400`.

### **Clear the pin**

- **Method:** `DELETE`
- **Endpoint:** `/api/uploadposts/users/linkedin-page`
- **Body (JSON):** `{"profile_username": "acme"}`

After clearing, uploads fall back to the default behaviour: the
`target_linkedin_page_id` parameter, or the personal profile when it is omitted.

### **Error Responses**

| Status | Meaning                                                                                                                                |
| :----- | :------------------------------------------------------------------------------------------------------------------------------------- |
| `400`  | Missing `profile_username` / `linkedin_page_id`, profile has no LinkedIn connected, token unavailable, or the Page is not administered by that account. |
| `404`  | Profile not found.                                                                                                                      |


---
# Upload-Post API Overview
URL: https://docs.upload-post.com/api/overview

# Upload-Post API Overview

Upload-Post provides a simple and powerful API for uploading content to TikTok, Instagram, Bluesky, LinkedIn, YouTube, Facebook, X (Twitter), Threads, Pinterest, Discord, Telegram, and Google Business Profile. This documentation will help you get started with our API and make the most of our services. Reddit posting is currently unavailable (HTTP 503 `error_code: "reddit_unavailable"`).

## Getting Started

1. Create an account at [upload-post.com](https://www.upload-post.com)
2. Connect your TikTok, Instagram, Bluesky, LinkedIn, YouTube, Facebook, X (Twitter), Threads, Pinterest, Discord, Telegram, and Google Business Profile accounts
3. Generate your API key from the dashboard
4. Start making API calls

## Authentication

All API requests require authentication using an API key. Include your API key in the request header:

```bash
Authorization: Apikey your-api-key-here
```

## Rate Limits

- Free tier: 10 uploads per month
- Additional uploads available through paid plans

## Base URL

All API endpoints are available at:
```
https://api.upload-post.com/api
```

For detailed information about each endpoint, check out our [API Reference](./reference).


---
# Photo Format Requirements
URL: https://docs.upload-post.com/api/photo-requirements

# Photo Format Requirements

This document outlines the photo format requirements for uploading to various social media platforms via the API. For platforms where specific requirements are not listed, standard image formats like JPEG and PNG are generally accepted. However, for the most accurate and up-to-date information, please consult the official documentation of each respective platform.

## Threads Photo Requirements

- **Format:** JPEG, PNG
- **File Size:** 8 MB maximum
- **Aspect Ratio:** Limit: 10:1 (e.g., can be from 1:10 to 10:1)
- **Width:**
  - Minimum: 320px (images narrower than 320px will be scaled up to 320px)
  - Maximum: 1440px (images wider than 1440px will be scaled down to 1440px)
- **Color Space:** sRGB (images with other color spaces will be converted to sRGB)
- **Items Per Post:** Up to 20 media items per post (carousel). If you provide more than 20 items, they are automatically distributed across multiple posts.
- **Thread Media Layout:** Use the `threads_thread_media_layout` parameter to control how media items are distributed across posts. For example, `"5,5"` splits 10 items into 2 posts of 5 each.

## Instagram Photo Requirements

- **Media Type:** The API supports "IMAGE" for feed posts and "STORIES".
- **Formats:** PNG, JPEG, GIF
- **File Size:** 8 MB maximum
- **Width:** 320–1440 px
- **Aspect Ratio:** 4:5 to 1.91:1
- **Alt text:** `instagram_alt_text` — up to 1,000 characters per image

## TikTok Photo Requirements

- **Format:** JPG, JPEG or WebP
- **File Size:** 20 MB maximum per image
- **Max Resolution:** 1080p — the short side may not exceed 1080 px and the long side 1920 px (1080 x 1920 portrait, 1920 x 1080 landscape, 1080 x 1080 square). Larger images are downscaled automatically before upload (aspect ratio preserved) and the change is listed in the response's `changes`; a 1500 x 1500 or a 2160 x 3840 image used to be rejected by TikTok with *"Unsupported image size"*.
- **Images Per Post:** Up to 35 images per photo post (slideshow)
- **Title:** Up to 90 characters. **Description:** up to 4,000 characters and 30 mentions
- **Rate limits:** 6 posts per minute and 15 posts per day per TikTok account
- Photo posts accept `privacy_level` and `photo_cover_index`; add background music with `auto_add_music`.

## Facebook Photo Requirements

- **Formats:** JPEG, PNG, GIF, BMP, TIFF, WEBP
- **File Size:** 4 MB maximum per photo (feed). Stories (`facebook_media_type=STORIES`): 10 MB
- Caption resolution: `facebook_description` > `description` > `title`

## X (Twitter) Photo Requirements

- **Formats:** JPEG, PNG, GIF, WEBP
- **Max File Size:** 5 MB per static image (`tweet_image`). Animated GIF: up to 15 MB (`tweet_gif`)
- **Images Per Tweet:** Up to 4 images per tweet. If you provide more than 4 images, they are automatically distributed across a thread (up to 4 images per tweet).
- **Thread Image Layout:** Use the `x_thread_image_layout` parameter to control how images are distributed across tweets in the thread. For example, `"4,4"` puts 4 images in each of 2 tweets.
- **Alt text:** `x_alt_text` — up to 1,000 characters per image

## LinkedIn Photo Requirements

- **Formats:** JPEG, PNG, GIF
- **Max pixels:** 36,152,320. Animated GIF: ≤ 250 frames
- **Alt text:** `linkedin_alt_text` (defaults to `title`)

## Pinterest Photo Requirements

- **Max Image Size:** 20 MB
- **Supported Formats:** BMP, JPEG, PNG, TIFF, GIF, Animated GIF, WEBP
- **Recommended Size:** 1000 x 1500 px
- **Aspect Ratio:** 2:3
- **Minimum Size:** 600 x 900 px
- **Maximum Size:** 2000 x 3000 px
- **Content-Type:** A valid media Content-Type such as image/jpeg, image/png, or image/webp returned by the hosting provider
- **Image Carousel:**
  - Up to five carousel images
  - Images must be the same dimension

## Reddit Photo Requirements

Reddit posting is currently unavailable. Uploads with `platform[]=reddit` return **HTTP 503** `error_code: "reddit_unavailable"`.

## Bluesky Photo Requirements

- **Max Images:** 4 per post by default. If you send more without gallery mode, Upload-Post publishes the first 4 and returns a `warnings` entry naming how many were dropped. `bluesky_gallery=true` publishes up to 20 images as `app.bsky.embed.gallery`.
- **Max File Size:** 1 MB per image
- **Supported Formats:** JPEG, PNG, GIF, WEBP
- **Alt Text:** `bluesky_alt_text` (string, JSON array, or `||`-separated)

## Discord Photo Requirements

- **Max Images:** 10 per message (sent as attachments)
- **Supported Formats:** JPG, PNG, GIF, WEBP (any image type Discord accepts)
- **Caption:** Up to 2,000 characters, applied as the message content
- **Max File Size:** Bounded by the upload limit of the target Discord server (the default Discord per-attachment limit applies)

## Telegram Photo Requirements

- **Max Images:** 10 per album (sent via `sendMediaGroup`; a single photo is sent via `sendPhoto`)
- **Supported Formats:** JPG, PNG, WEBP (any image type Telegram accepts)
- **Caption:** Up to 1,024 characters, applied to the first item of the album

---
*Note: The information for Instagram, TikTok, Facebook, X (Twitter), and LinkedIn photo requirements above is general. The provided source code focused primarily on video specifications and Threads image specifications. Always check the official platform guidelines for the latest and most precise requirements.*


---
# Post actions
URL: https://docs.upload-post.com/api/post-actions

# Post actions

These used to live on one page. Each action is now its own doc:

- [Retry a failed upload](./retry-post.md) — `POST /api/uploadposts/posts/retry`
- [Unpublish a published post](./unpublish-post.md) — `POST /api/uploadposts/posts/unpublish`
- [Edit a published post](./edit-post.md) — `POST /api/uploadposts/posts/edit`
- [Repost](./repost.md) — `POST /api/uploadposts/posts/repost`
- [Save a Pinterest pin](./save-pin.md) — `POST /api/uploadposts/posts/save`


---
# Queue System
URL: https://docs.upload-post.com/api/queue-system

# Queue System

The queue system allows you to automatically schedule posts to predefined time slots. Instead of specifying an exact date/time with `scheduled_date`, you can use `add_to_queue=true` to have the system automatically assign your post to the next available slot.

## How It Works

The queue is **always active** with default time slots (9am, 12pm, 5pm Eastern Time). You can customize these slots, timezone, and active days through the Queue Settings endpoints.

When you upload content with `add_to_queue=true`:
1. The system finds the next available slot based on your queue configuration
2. Your post is automatically scheduled to that slot
3. You receive a `job_id` to track the scheduled post

### Multiple Posts Per Slot

By default, each slot accepts **1 post**. You can increase this with the `max_posts_per_slot` setting to allow multiple posts in the same time slot. This is useful when you want to post to different platforms at the same time (e.g., an Instagram post and a Facebook post both at 9am).

You can also **mark individual slots as full** to prevent new posts from being added, even if they haven't reached the maximum capacity.

---

## Using the Queue in Uploads

Add the `add_to_queue` parameter to any upload endpoint:

| Name | Type | Required | Description |
|------|------|----------|-------------|
| add_to_queue | Boolean | No | If `true`, automatically schedules the post to your next available queue slot. Cannot be used together with `scheduled_date`. |
| max_posts_per_slot | Integer | No | Override the profile's `max_posts_per_slot` setting for this request. Only used when `add_to_queue=true`. |

### Example Request

```bash
curl -X POST "https://api.upload-post.com/api/upload" \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -F "user=my_profile" \
  -F "platform[]=instagram" \
  -F "platform[]=tiktok" \
  -F "video=@my_video.mp4" \
  -F "title=My awesome video" \
  -F "add_to_queue=true"
```

### Example with Multiple Posts Per Slot

```bash
# First post goes to 9am slot
curl -X POST "https://api.upload-post.com/api/upload" \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -F "user=my_profile" \
  -F "platform[]=instagram" \
  -F "video=@video1.mp4" \
  -F "title=Instagram video" \
  -F "add_to_queue=true" \
  -F "max_posts_per_slot=3"

# Second post also goes to 9am slot (same slot, different platform)
curl -X POST "https://api.upload-post.com/api/upload" \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -F "user=my_profile" \
  -F "platform[]=facebook" \
  -F "video=@video2.mp4" \
  -F "title=Facebook video" \
  -F "add_to_queue=true" \
  -F "max_posts_per_slot=3"
```

### Success Response `202 Accepted`

```json
{
  "success": true,
  "job_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "scheduled_date": "2025-01-30T14:00:00+00:00",
  "queue_slot": "2025-01-30T14:00:00+00:00",
  "message": "Post added to queue"
}
```

---

## Get Queue Settings

Retrieve the current queue configuration for a profile.

| | |
|---|---|
| **Endpoint** | `GET /api/uploadposts/queue/settings` |
| **Authentication** | **Required**. `Authorization: Apikey <token>` |

### Query Parameters

| Name | Type | Required | Description |
|------|------|----------|-------------|
| profile_username | String | Yes | The profile to get settings for |

### Success Response `200 OK`

```json
{
  "success": true,
  "queue_settings": {
    "timezone": "America/New_York",
    "slots": [
      { "hour": 9, "minute": 0 },
      { "hour": 12, "minute": 0 },
      { "hour": 17, "minute": 0 }
    ],
    "days_of_week": [0, 1, 2, 3, 4, 5, 6],
    "max_posts_per_slot": 1,
    "full_slots": []
  }
}
```

| Field | Type | Description |
|-------|------|-------------|
| timezone | String | IANA timezone for the queue slots (e.g., "America/New_York", "Europe/Madrid") |
| slots | Array | Array of time slots with `hour` (0-23) and `minute` (0-59) |
| days_of_week | Array | Active days: 0=Monday, 1=Tuesday, ..., 6=Sunday |
| max_posts_per_slot | Integer | Maximum number of posts allowed per time slot (default: 1) |
| full_slots | Array | List of ISO 8601 datetimes that have been manually marked as full |

---

## Update Queue Settings

Update the queue configuration for a profile.

| | |
|---|---|
| **Endpoint** | `POST /api/uploadposts/queue/settings` |
| **Authentication** | **Required**. `Authorization: Apikey <token>` |

### Body Parameters (JSON)

| Name | Type | Required | Description |
|------|------|----------|-------------|
| profile_username | String | Yes | The profile to update settings for |
| timezone | String | No | IANA timezone (e.g., "Europe/London"). See [valid timezones](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones). |
| slots | Array | No | Array of slot objects: `[{ "hour": 9, "minute": 0 }, ...]`. Max 24 slots. |
| days_of_week | Array | No | Array of active days (0-6). Example: `[0, 1, 2, 3, 4]` for Monday-Friday. |
| max_posts_per_slot | Integer | No | Maximum posts per slot (1-100). Default: 1. Set higher to allow multiple posts in the same time slot. |

### Example Request

```bash
curl -X POST "https://api.upload-post.com/api/uploadposts/queue/settings" \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "profile_username": "my_profile",
    "timezone": "Europe/Madrid",
    "slots": [
      { "hour": 8, "minute": 30 },
      { "hour": 13, "minute": 0 },
      { "hour": 19, "minute": 30 }
    ],
    "days_of_week": [0, 1, 2, 3, 4],
    "max_posts_per_slot": 3
  }'
```

### Success Response `200 OK`

```json
{
  "success": true,
  "queue_settings": {
    "timezone": "Europe/Madrid",
    "slots": [
      { "hour": 8, "minute": 30 },
      { "hour": 13, "minute": 0 },
      { "hour": 19, "minute": 30 }
    ],
    "days_of_week": [0, 1, 2, 3, 4],
    "max_posts_per_slot": 3
  }
}
```

---

## Get Queue Preview

Preview the next upcoming queue slots and their availability.

| | |
|---|---|
| **Endpoint** | `GET /api/uploadposts/queue/preview` |
| **Authentication** | **Required**. `Authorization: Apikey <token>` |

### Query Parameters

| Name | Type | Required | Description |
|------|------|----------|-------------|
| profile_username | String | Yes | The profile to preview |
| count | Integer | No | Number of slots to return (default: 10, max: 50) |

### Success Response `200 OK`

```json
{
  "success": true,
  "timezone": "America/New_York",
  "max_posts_per_slot": 3,
  "slots": [
    {
      "datetime_utc": "2025-01-30T14:00:00+00:00",
      "datetime_local": "2025-01-30T09:00:00-05:00",
      "available": true,
      "post_count": 0,
      "max_posts_per_slot": 3,
      "is_full": false,
      "manually_full": false
    },
    {
      "datetime_utc": "2025-01-30T17:00:00+00:00",
      "datetime_local": "2025-01-30T12:00:00-05:00",
      "available": true,
      "post_count": 2,
      "max_posts_per_slot": 3,
      "is_full": false,
      "manually_full": false,
      "scheduled_posts": [
        {
          "job_id": "abc123",
          "title": "My Instagram post",
          "platforms": ["instagram"]
        },
        {
          "job_id": "def456",
          "title": "My Facebook post",
          "platforms": ["facebook"]
        }
      ]
    },
    {
      "datetime_utc": "2025-01-30T22:00:00+00:00",
      "datetime_local": "2025-01-30T17:00:00-05:00",
      "available": false,
      "post_count": 3,
      "max_posts_per_slot": 3,
      "is_full": true,
      "manually_full": false,
      "scheduled_posts": [...]
    }
  ],
  "next_available": "2025-01-30T14:00:00+00:00"
}
```

| Field | Type | Description |
|-------|------|-------------|
| post_count | Integer | Number of posts currently scheduled in this slot |
| max_posts_per_slot | Integer | Maximum posts allowed per slot |
| is_full | Boolean | `true` if the slot is at capacity or manually marked as full |
| manually_full | Boolean | `true` if the slot was manually marked as full via the Mark Slot Full endpoint |
| scheduled_posts | Array | List of all posts scheduled in this slot (when multiple posts per slot is enabled) |
| scheduled_post | Object | First scheduled post in the slot (for backward compatibility) |

---

## Mark Slot Full

Manually mark a specific queue slot as full, preventing new posts from being added to it even if it hasn't reached `max_posts_per_slot`.

| | |
|---|---|
| **Endpoint** | `POST /api/uploadposts/queue/slot-full` |
| **Authentication** | **Required**. `Authorization: Apikey <token>` |

### Body Parameters (JSON)

| Name | Type | Required | Description |
|------|------|----------|-------------|
| profile_username | String | Yes | The profile to update |
| slot_datetime | String | Yes | ISO 8601 datetime of the slot to mark as full (UTC) |

### Example Request

```bash
curl -X POST "https://api.upload-post.com/api/uploadposts/queue/slot-full" \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "profile_username": "my_profile",
    "slot_datetime": "2025-01-30T14:00:00+00:00"
  }'
```

### Success Response `200 OK`

```json
{
  "success": true,
  "message": "Slot 2025-01-30T14:00:00+00:00 marked as full",
  "full_slots": ["2025-01-30T14:00:00+00:00"]
}
```

---

## Unmark Slot Full

Remove the full mark from a slot, allowing new posts to be added again.

| | |
|---|---|
| **Endpoint** | `DELETE /api/uploadposts/queue/slot-full` |
| **Authentication** | **Required**. `Authorization: Apikey <token>` |

### Body Parameters (JSON)

| Name | Type | Required | Description |
|------|------|----------|-------------|
| profile_username | String | Yes | The profile to update |
| slot_datetime | String | Yes | ISO 8601 datetime of the slot to unmark (UTC) |

### Example Request

```bash
curl -X DELETE "https://api.upload-post.com/api/uploadposts/queue/slot-full" \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "profile_username": "my_profile",
    "slot_datetime": "2025-01-30T14:00:00+00:00"
  }'
```

### Success Response `200 OK`

```json
{
  "success": true,
  "message": "Slot 2025-01-30T14:00:00+00:00 unmarked as full",
  "full_slots": []
}
```

---

## Get Next Available Slot

Get the next available queue slot for a profile.

| | |
|---|---|
| **Endpoint** | `GET /api/uploadposts/queue/next-slot` |
| **Authentication** | **Required**. `Authorization: Apikey <token>` |

### Query Parameters

| Name | Type | Required | Description |
|------|------|----------|-------------|
| profile_username | String | Yes | The profile to check |

### Success Response `200 OK`

```json
{
  "success": true,
  "next_slot": {
    "datetime_utc": "2025-01-30T14:00:00+00:00",
    "datetime_local": "2025-01-30T09:00:00-05:00",
    "timezone": "America/New_York"
  }
}
```

If no slots are available within the next 30 days:

```json
{
  "success": true,
  "next_slot": null,
  "message": "No available slots found"
}
```

---

## Default Configuration

If you haven't customized your queue settings, these defaults apply:

| Setting | Default Value |
|---------|---------------|
| Timezone | America/New_York (Eastern Time) |
| Slots | 9:00 AM, 12:00 PM, 5:00 PM |
| Days of week | All days (Monday-Sunday) |
| Max posts per slot | 1 |

---

## See Also

- [Upload Video](./upload-video.md) - Video upload endpoint
- [Upload Photos](./upload-photo.md) - Photo upload endpoint
- [Upload Text](./upload-text.md) - Text post endpoint
- [Schedule Posts](./schedule-posts.md) - Manage scheduled posts
- [Upload Status](./upload-status.md) - Check upload/job status


---
# API Reference
URL: https://docs.upload-post.com/api/reference

# API Reference

The Upload-Post API provides comprehensive endpoints for content management across multiple social media platforms. All endpoints require authentication via API key in the `Authorization` header.

## Core Upload APIs

### [Video Upload API](./upload-video.md)

Upload videos to TikTok, Instagram, LinkedIn, YouTube, Facebook, X (Twitter), Threads, Pinterest, Bluesky, Discord, Telegram, and Google Business Profile. Supports both synchronous and asynchronous uploads with scheduling capabilities. Reddit posting is currently unavailable (HTTP 503 `error_code: "reddit_unavailable"`).

**Endpoint:** `POST /api/upload`

**Supported Platforms:** TikTok, Instagram, LinkedIn, YouTube, Facebook, X (Twitter), Threads, Pinterest, Bluesky, Discord, Telegram, Google Business Profile, Mastodon, WordPress. Reddit posting is currently unavailable (`reddit_unavailable`).

### [Photo Upload API](./upload-photo.md)

Upload photos and image carousels to LinkedIn, Facebook, X (Twitter), Instagram, TikTok, Threads, Pinterest, Bluesky, Discord, Telegram, and Google Business Profile. Perfect for visual content distribution across platforms. Reddit posting is currently unavailable.

**Endpoint:** `POST /api/upload_photos`

**Supported Platforms:** LinkedIn, Facebook, X (Twitter), Instagram, TikTok, Threads, Pinterest, Bluesky, Discord, Telegram, Google Business Profile, Mastodon, Lemmy, WordPress. Reddit posting is currently unavailable (`reddit_unavailable`).

### [Text Upload API](./upload-text.md)

Create and distribute text-only posts across social platforms. Ideal for announcements, updates, and text-based content.

**Endpoint:** `POST /api/upload_text`

**Supported Platforms:** X (Twitter), LinkedIn, Facebook, Threads, Bluesky, Discord, Telegram, Google Business Profile, Slack, Mastodon, Nostr, Lemmy, Dev.to, Hashnode, WordPress, Whop, Listmonk. Reddit posting is currently unavailable (`reddit_unavailable`).

**Also covers:** [LinkedIn polls](./upload-text.md#linkedin-polls) (`linkedin_poll_question` + `linkedin_poll_options[]`) and [long-form X Articles](./upload-text.md#x-articles-long-form) (`x_article_title`, publish or save as draft).

### Platform How-To Guides

Step-by-step guides for the most requested platforms, with cURL, Python and JavaScript examples:

- [Post to TikTok via API](../guides/post-to-tiktok-api.md) — no TikTok developer app or audit required
- [Post to Instagram via API](../guides/post-to-instagram-api.md) — without your own Meta app approval
- [Post to YouTube via API](../guides/post-to-youtube-api.md) — no Google Cloud quota setup
- [Post to LinkedIn via API](../guides/post-to-linkedin-api.md) — profiles, company pages and documents
- [Post to X (Twitter) via API](../guides/post-to-x-twitter-api.md) — tweets, threads and media
- [Labeling AI-Generated Content](../guides/ai-content-labeling.md) — the `is_ai_generated` flag and per-platform AI-disclosure mapping (TikTok, Instagram, YouTube, X)

## Upload Management APIs

### [Webhooks & Notifications](./webhooks.md)

Real-time `POST` notifications for upload results and social account connection changes, signed with HMAC-SHA256 (`X-Upload-Post-Signature`). Account-level and per-profile webhooks.

**Endpoints:** `GET/POST/DELETE /api/uploadposts/users/notifications`, `POST /api/uploadposts/users/webhook-secret`, `GET/POST/DELETE /api/uploadposts/users/profile-webhook`

### [Upload Status](./upload-status.md)

Track the progress and results of asynchronous uploads initiated with `async_upload=true` or scheduled posts. Essential for monitoring long-running upload operations and checking scheduled post execution status.

**Endpoint:** `GET /api/uploadposts/status`

**Parameters:** `request_id` (for async uploads) or `job_id` (for scheduled posts)

**Use Case:** Check status of background uploads and scheduled posts, get detailed results per platform

### [Upload History](./upload-history.md)

Retrieve a paginated history of all your past uploads across platforms. Includes detailed metadata, success/failure status, and platform-specific information.

**Endpoint:** `GET /api/uploadposts/history`

**Features:** Pagination, filtering, comprehensive upload metadata

**Filters:** `platform`, `status`, `profile_username`, `start`/`end`, and exact `request_id` / `job_id` / `external_id` lookups

### [Schedule Management](./schedule-posts.md)

Schedule posts for future publication across supported platforms. Manage your content calendar programmatically.

**Endpoint:** Various scheduling endpoints

**Supported Platforms:** X (Twitter), LinkedIn, Facebook, Instagram, TikTok, Bluesky, Threads, Pinterest, YouTube, Discord, Telegram

**Correlation:** Send an `external_id` on upload to map scheduled jobs back to records in your own system without matching on the (editable) title.

### [AI Shorts API](./ai-shorts.md)

Send a short-form video and get AI-written titles, descriptions, captions and hashtags per platform, or rewrite existing captions into another language. Uses the monthly AI Shorts quota of your plan.

**Endpoints:** `POST /api/uploadposts/analyze-shorts`, `POST /api/uploadposts/rewrite-captions`

**Supported Platforms:** YouTube, Instagram, TikTok, Facebook

### [Set YouTube Thumbnail](./youtube-thumbnail.md)

Set or replace the custom thumbnail of an already-published YouTube video from a file or a public URL.

**Endpoint:** `POST /api/uploadposts/youtube/thumbnail`

**Supported Platforms:** YouTube

## Instagram Interactions

### [Media List](./instagram-media.md)

Retrieve a list of recent media (posts, reels, videos, pins, tweets, etc.) from any connected social media account. Supports Instagram, TikTok, YouTube, LinkedIn, Facebook, X, Threads, Pinterest, Bluesky, and Reddit.

**Endpoint:** `GET /api/uploadposts/media`

**Returns:** Media IDs, captions, media types, permalinks, timestamps, thumbnail URLs

**Pagination:** `limit` (default 25, max 100, capped per platform) and `cursor` for cursor-based paging. LinkedIn, Discord and Telegram support `limit` only.

### [Comments (all platforms)](./comments.md)

List, create and delete comments on your posts across Instagram, Facebook, YouTube, LinkedIn, TikTok, X, Threads and Bluesky with one consistent API. Reddit comments return 503 `reddit_unavailable`.

**Endpoints:**

- `GET /api/uploadposts/comments` - List comments on a post; add `comment_id` to get the replies under one comment instead
- `POST /api/uploadposts/comments/create` - Comment on a post or reply to a comment
- `DELETE /api/uploadposts/comments/delete` - Delete a comment
- `POST /api/uploadposts/comments/action` - platform-specific verbs (`hide` / `unhide`, `like` / `unlike`, `pin` / `unpin`, Facebook `edit`, YouTube `hold`, Instagram `enable_comments` / `disable_comments`, Threads `approve` / `ignore`)

**TikTok:** supported on accounts that report the `comments` [capability](./user-profiles.md#account-capabilities), i.e. reconnected accounts. `post_id` is the video id. A comment created on TikTok takes ~10 s to appear in a listing.

### [Instagram Comments](./instagram-comments.md)

Retrieve comments on Instagram posts and send private replies (DMs) to commenters. Supports both media IDs and post URLs.

**Endpoints:**

- `GET /api/uploadposts/comments` - Get comments on an Instagram post
- `POST /api/uploadposts/comments/reply` - Send a private reply DM to a commenter

**Required Permission:** `instagram_business_manage_comments`

### [Instagram Direct Messages](./instagram-dms.md)

Send direct messages to Instagram users and retrieve DM conversations. Supports customer support workflows and follow-up messaging within Instagram's 24-hour messaging window policy.

**Endpoints:**

- `POST /api/uploadposts/dms/send` - Send a DM to an Instagram user by IGSID
- `GET /api/uploadposts/dms/conversations` - Retrieve Instagram DM conversations

**Required Permission:** `instagram_business_manage_messages`

### [AutoDM Monitors](./autodms.md)

Set up persistent monitors that automatically send private DMs to users who comment on your Instagram posts. Monitors run in the background 24/7 with built-in duplicate prevention and rate limiting.

**Endpoints:**

- `POST /api/uploadposts/autodms/start` - Start a new comment monitor
- `GET /api/uploadposts/autodms/status` - Get status of all monitors (supports `?include_inactive=true` to also return stopped/expired ones)
- `GET /api/uploadposts/autodms/logs` - Get activity logs for a monitor
- `POST /api/uploadposts/autodms/pause` - Pause a monitor
- `POST /api/uploadposts/autodms/resume` - Resume a paused monitor
- `POST /api/uploadposts/autodms/stop` - Stop a monitor
- `POST /api/uploadposts/autodms/delete` - Delete a monitor

**Limits:** 2 monitors per profile per day, auto-expires after 15 days

## Platform Integration APIs

### [Analytics API](./get-analytics.md)

Retrieve detailed analytics and performance metrics for your social media profiles across connected platforms.

**Endpoint:** `GET /api/analytics/{profile_username}`

**Supported Platforms:** Instagram, TikTok, LinkedIn, Facebook, X (Twitter), YouTube, Threads, Pinterest, Reddit

**Metrics:** Followers, impressions, reach, profile views, time-series data, metric_type, Instagram `follower_demographics` and `engaged_audience_demographics`

### [Total Impressions & Post Analytics](./get-analytics.md)

Get unified total impressions aggregated across platforms, per-post analytics, and platform metrics config. All analytics endpoints are consolidated in the Analytics API page.

**Endpoints:** `GET /api/uploadposts/total-impressions/{profile_username}` · `GET /api/uploadposts/post-analytics/{request_id}` · `GET /api/uploadposts/post-analytics?platform_post_id=` · `GET /api/uploadposts/post-analytics/cached`

**Features:** Date range filtering, per-platform breakdown, live post metrics, profile snapshots, analytics for organic posts via platform_post_id, and the full TikTok per-post breakdown (retention curve, impression sources, audience types, reach, favorites, new followers)

### [Cached Post Analytics](./get-analytics.md#get-apiuploadpostspost-analyticscached)

Bulk replay of per-post metrics Upload-Post already fetched, instead of calling the platforms again. Only contains posts previously read through a live endpoint; there is no background refresh. Not subject to the 100 requests / 5 minutes platform analytics rate limit.

**Endpoint:** `GET /api/uploadposts/post-analytics/cached`

**Features:** Cursor pagination (up to 200 posts per page), `platform` filter, `since`/`until` date range, per-row `captured_at` freshness marker

### [Audience Insights](./audience.md)

Who follows an account, when they are online, what they tap on the profile and
how it compares to its category. One endpoint with a `platform`, the same shape
as `/comments` and `/post-analytics`. `activity_by_hour` is the field to build a
publishing schedule on.

**Endpoint:** `GET /api/uploadposts/audience`

**Returns:** `range` (the window actually used, after trimming), `audience` (countries / cities / ages / genders), `activity_by_hour`, `followers_daily`, `profile_actions`, `bio_description`, `benchmark_categories`, and `benchmark` when `benchmark_category` is sent

**Supported platforms:** `tiktok`

**Required capability:** `profile_analytics`

### [Get Facebook Pages](./get-facebook-pages.md)

Retrieve all Facebook pages accessible through connected accounts. Required for posting to specific Facebook pages.

**Endpoint:** `GET /api/uploadposts/facebook/pages`

**Returns:** Page IDs, names, profile pictures, account associations

### [Pin a Facebook Page to a Profile](./facebook-page.md)

Pin one Facebook Page to a profile so every upload from that profile targets it without passing `facebook_page_id`. The pin takes precedence over the request parameter.

**Endpoint:** `GET/POST/DELETE /api/uploadposts/users/facebook-page`

**Returns:** Available Pages plus the pinned `facebook_page_id` / `facebook_page_name`

### [Get LinkedIn Pages](./get-linkedin-pages.md)

Fetch LinkedIn company pages associated with your connected accounts. Essential for business page posting.

**Endpoint:** `GET /api/uploadposts/linkedin/pages`

**Returns:** Organization URNs, company names, vanity URLs, page logos

### [Pin a LinkedIn Page to a Profile](./linkedin-page.md)

Pin one LinkedIn company Page to a profile so every upload from that profile posts as that Page without passing `target_linkedin_page_id`. The pin takes precedence over the request parameter.

**Endpoint:** `GET/POST/DELETE /api/uploadposts/users/linkedin-page`

**Returns:** Available Pages plus the pinned `linkedin_page_id` / `linkedin_page_name`

### [Get Google Business Locations](./get-google-business-locations.md)

List the Google Business Profile locations available to an account. Essential for agencies and SaaS platforms where users manage multiple locations. Pass the chosen location's `name` as `gbp_location_id` on the upload request, or [pin it to the profile](./google-business-location.md) once and omit the parameter.

**Endpoint:** `GET /api/uploadposts/google-business/locations`

**Returns:** Location IDs and business names

**Post destinations:** Local Posts (the "Updates" tab, default) or the location's [Media/Gallery tab](./upload-photo.md#publishing-to-the-mediagallery-tab) via `gbp_post_type=MEDIA` + `gbp_media_category`

### [Pin a Google Business Location to a Profile](./google-business-location.md)

Pin one Google Business location to a profile so every upload from that profile publishes there without passing `gbp_location_id`. Google's OAuth chooser only picks the Google account, so this is how a multi-location account stops being asked to select one on every post. The pin takes precedence over the request parameter.

**Endpoint:** `GET/POST/DELETE /api/uploadposts/users/google-business-location`

**Returns:** Available locations plus the pinned `gbp_location_id` / `gbp_location_name`

### [Get Pinterest Boards](./get-pinterest-boards.md)

List all Pinterest boards (public and secret) from connected accounts. Required for targeting specific boards when pinning content.

**Endpoint:** `GET /api/uploadposts/pinterest/boards`

**Returns:** Board IDs, names, associated Pinterest accounts

### [Get TikTok Trending Music](./get-tiktok-music.md)

List trending tracks from TikTok's Commercial Music Library — the catalogue of
songs you can attach to a video through the API with `tiktok_music_id`.

**Endpoint:** `GET /api/uploadposts/tiktok/music/trending`

**Returns:** Track `id` (what you send as `tiktok_music_id`), titles, artists, duration, artwork and audio previews

### [Search TikTok Music](./search-tiktok-music.md)

Search the Commercial Music Library by song title or artist. TikTok has no music
search endpoint, so the search runs over the trending charts Upload-Post caches
— not over TikTok's whole catalogue.

**Endpoint:** `GET /api/uploadposts/tiktok/music/search`

**Returns:** The same track objects as the trending endpoint, ranked by relevance

### [Get TikTok Locations](./get-tiktok-locations.md)

Search TikTok places to tag a location on a post with `tiktok_location_id` +
`tiktok_location_name`.

**Endpoint:** `GET /api/uploadposts/tiktok/locations`

**Returns:** `location_id`, `location_name` and postal address (max 20 matches)

### [Get TikTok Publishing Settings](./get-tiktok-settings.md)

What a connected TikTok account is actually allowed to publish. TikTok narrows
the four `privacy_level` values per account — a private account has no
`PUBLIC_TO_EVERYONE` — so ask here before offering them.

**Endpoint:** `GET /api/uploadposts/tiktok/settings`

**Returns:** `privacy_level_options`, `max_video_post_duration_sec` and the account's comment / duet / stitch switches

### [Instagram Publishing Limit](./get-instagram-publishing-limit.md)

Remaining Instagram content-publishing quota for a connected profile.

**Endpoint:** `GET /api/uploadposts/instagram/publishing_limit`

### [Threads Publishing Limit](./get-threads-publishing-limit.md)

Remaining Threads posts and replies quota (250 posts / 1000 replies per 24 hours).

**Endpoint:** `GET /api/uploadposts/threads/publishing_limit`

### [Retry a Failed Upload](./retry-post.md)

Re-enqueue platforms that failed on an upload. The original media snapshot is reused.

**Endpoint:** `POST /api/uploadposts/posts/retry`

### [Unpublish a Published Post](./unpublish-post.md)

Delete a live post. Instagram, TikTok and Threads are not supported.

**Endpoint:** `POST /api/uploadposts/posts/unpublish`

### [Edit a Published Post](./edit-post.md)

Update caption or metadata on a live Facebook, LinkedIn, X, YouTube or Google Business post.

**Endpoint:** `POST /api/uploadposts/posts/edit`

### [Repost](./repost.md)

Reshare a LinkedIn post or retweet an X post.

**Endpoint:** `POST /api/uploadposts/posts/repost`

### [Save a Pinterest Pin](./save-pin.md)

Save (repin) a Pinterest pin onto a board.

**Endpoint:** `POST /api/uploadposts/posts/save`

### [Suggestions](./suggestions.md)

What to tag a post with and what people search around a keyword. One endpoint
with a `platform` and a `type`, not one per network.

**Endpoint:** `GET /api/uploadposts/suggestions`

**Returns:** `hashtags[]` with `name` (no leading `#`) and `view_count` for `type=hashtags`; `keywords[]` with each term and its search volume for `type=keywords`

**Supported platforms:** `tiktok`

**Required capability:** `profile_analytics` for `type=hashtags`; `trend_search` (reconnected account) for `type=keywords`

## Paid Ads APIs

### [ChatGPT Ads](./chatgpt-ads.md)

Run ads inside ChatGPT from the same API key you publish with. Connect an OpenAI
Ads account once, then create campaigns, ad groups and ads, upload creatives and
pull performance insights. Spend runs on your own OpenAI billing; every object is
created paused unless you ask for `active`.

**Endpoints:** `POST /api/uploadposts/ads/chatgpt/connect`, `GET/DELETE /api/uploadposts/ads/chatgpt/accounts`, `GET/POST /api/uploadposts/ads/chatgpt/campaigns`, `GET/POST /api/uploadposts/ads/chatgpt/adgroups`, `GET/POST /api/uploadposts/ads/chatgpt/ads`, `POST /api/uploadposts/ads/chatgpt/creatives`, `POST /api/uploadposts/ads/chatgpt/promote`, `GET /api/uploadposts/ads/chatgpt/insights`

**Requires:** an OpenAI Ads Manager account and an API key from [ads.openai.com](https://ads.openai.com) (one key per ad account)

## User Management APIs

### [User Profiles API](./user-profiles.md)

Manage user profiles and generate JWTs for linking social accounts when integrating Upload-Post into your own platform. Essential for white-label integrations and multi-user applications.

**Endpoints:**

- `POST /api/uploadposts/users` - Create user profiles
- `GET /api/uploadposts/users` - Retrieve user profiles
- `DELETE /api/uploadposts/users` - Delete user profiles
- `POST /api/uploadposts/users/generate-jwt` - Generate authentication tokens
- `GET /api/uploadposts/users/validate-jwt` - Validate tokens

**White-label:** the connect page supports `en`, `es`, `de`, `fr`, `pt`, `pl` and `tr`, plus per-key text overrides via [`ui_labels`](./user-profiles.md#custom-ui-labels).

See the [User Profile Integration Guide](../guides/user-profile-integration.md) for implementation workflow.

### [Connect API](./connect-api.md)

Build your own social account connection page instead of using the hosted one. Server-side OAuth start endpoints return the authorize URL for each platform; a single-use state authenticates the callback, and the end user returns to your `redirect_url` with `connect_status=success|cancelled|error` and a stable `error_code` on failure.

**Endpoint:**

- `POST /api/uploadposts/oauth/{platform}/start` - Get the authorize URL for a platform

**Supported Platforms:** TikTok, Instagram, Facebook, LinkedIn, YouTube, X (Twitter), Threads, Reddit, Pinterest, Google Business Profile, Snapchat

### [Current User API](./current-user.md)

Validate your API key and retrieve basic account information including email and subscription plan.

**Endpoint:** `GET /api/uploadposts/me`

**Use Case:** API key validation, plan verification, account confirmation

## Content Requirements

### [Photo Requirements](./photo-requirements.md)

Comprehensive format specifications, file size limits, aspect ratios, and technical requirements for photo uploads across all supported platforms.

**Covers:** Instagram, TikTok, Facebook, X (Twitter), LinkedIn, Threads, Pinterest, Reddit, Bluesky, Discord, Telegram

### [Video Requirements](./video-requirements.md)

Detailed video format requirements, codec specifications, resolution limits, and encoding guidelines for optimal compatibility across platforms.

**Covers:** TikTok, Instagram, YouTube, LinkedIn, Facebook, X (Twitter), Threads, Pinterest, Reddit, Bluesky, Discord, Telegram

**Includes:** FFmpeg re-encoding solutions for compatibility issues

## MCP Server (AI Agents)

### [MCP Server Integration](../guides/mcp-server-integration.md)

Every endpoint on this page is also exposed through Upload-Post's official, open-source **Model Context Protocol** server. Point ChatGPT, claude.ai, Claude Desktop, Claude Code or Cursor at it and your agent can publish, schedule and analyze content without a hand-written REST client.

**Hosted endpoint:** `https://mcp.upload-post.com/mcp`

**In ChatGPT:** listed as a reviewed app in the ChatGPT app directory — [add Upload-Post in ChatGPT](https://chatgpt.com/plugins/plugin_asdk_app_6a1fed02ff5c8191b4060c5807221f26) in one click, no Developer mode required

**Authentication:** API key, or OAuth 2.1 with PKCE for agents that authorize themselves

**Source:** [github.com/Upload-Post/upload-post-mcp](https://github.com/Upload-Post/upload-post-mcp) (MIT)

## Getting Started

1. **Authentication:** All requests require an API key in the `Authorization: Apikey your-api-key-here` header
2. **Base URL:** `https://api.upload-post.com/api`
3. **Rate Limits:** Free tier includes 10 uploads per month
4. **Content Guidelines:** Review platform-specific requirements before uploading

For implementation examples and integration guides, see our [SDK Examples](../sdk-examples.md) and [Integration Guides](../guides/user-profile-integration.md).


---
# Repost
URL: https://docs.upload-post.com/api/repost

# Repost

Reshare a post that is already live. LinkedIn creates a new share with optional commentary. X retweets the original post.

## Platform support

| Platform | Repost |
| :------- | :----: |
| LinkedIn |   ✅   |
| X        |   ✅   |

## Endpoint

```
POST /api/uploadposts/posts/repost
```

Alias: `POST /api/uploadposts/posts/action` with `"action": "repost"` (LinkedIn).

## Headers

| Name          | Value                    | Description                     |
| :------------ | :----------------------- | :------------------------------ |
| Authorization | Apikey your-api-key-here | Your API key for authentication |
| Content-Type  | application/json         | Request body format             |

## Body Parameters (JSON)

| Name | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `platform` | String | Yes | `linkedin` or `x`. |
| `user` | String | Yes | Profile username. |
| `post_id` | String | Yes | Post to repost. |
| `commentary` | String | No | LinkedIn reshare commentary. |
| `target_linkedin_page_id` | String | No | LinkedIn organization to reshare as. |

## Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/posts/repost \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "linkedin",
    "user": "my-profile",
    "post_id": "urn:li:share:123",
    "commentary": "Worth sharing"
  }'
```

X:

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/posts/repost \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "x",
    "user": "my-profile",
    "post_id": "1234567890123456789"
  }'
```

## Successful Response (`200 OK`)

LinkedIn:

```json
{
  "success": true,
  "message": "LinkedIn post reshared.",
  "post_id": "urn:li:share:456",
  "url": "https://www.linkedin.com/feed/update/urn:li:share:456/",
  "parent_post_id": "urn:li:share:123",
  "author": "urn:li:person:789"
}
```

X:

```json
{
  "success": true,
  "platform": "x",
  "post_id": "1234567890123456789",
  "reposted": true,
  "repost_id": "9876543210"
}
```

## Error Responses

- **400 Bad Request** — missing fields, or a platform other than `linkedin` / `x`.
- **403 Forbidden** — the connected account is not authorized to repost.
- **404 Not Found** — no post found for the given `post_id`.
- **500 Internal Server Error**

## Related

- [Edit a published post](./edit-post.md)
- [Unpublish](./unpublish-post.md)
- [Save a Pinterest pin](./save-pin.md)


---
# Retry a Failed Upload
URL: https://docs.upload-post.com/api/retry-post

# Retry a Failed Upload

Re-enqueue an upload that failed on one or more platforms. Only the platforms that failed are retried; platforms that already succeeded are left untouched. The original media snapshot is reused, so **you do not need to re-upload the file**.

## Endpoint

```
POST /api/uploadposts/posts/retry
```

## Headers

| Name          | Value                    | Description                     |
| :------------ | :----------------------- | :------------------------------ |
| Authorization | Apikey your-api-key-here | Your API key for authentication |
| Content-Type  | application/json         | Request body format             |

## Body Parameters (JSON)

| Name         | Type   | Required | Description                                              |
| :----------- | :----- | :------- | :------------------------------------------------------- |
| `request_id` | String | Yes\*    | The `request_id` returned by the original async upload.  |
| `job_id`     | String | Yes\*    | The scheduled job ID. Alternative to `request_id`.       |

\* Provide either `request_id` or `job_id` (one is required).

## Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/posts/retry \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "request_id": "3f7c2b1a-9d4e-4a2b-8c1f-1234567890ab"
  }'
```

## Successful Response (`200 OK`)

```json
{
  "success": true,
  "request_id": "3f7c2b1a-9d4e-4a2b-8c1f-1234567890ab",
  "message": "Failed platforms re-enqueued for retry"
}
```

Poll the [upload status](./upload-status.md) endpoint with the `request_id` to track the retried platforms.

## Error Responses

- **400 Bad Request** — neither `request_id` nor `job_id` provided.
- **404 Not Found** — no upload found for the given identifier.
- **409 Conflict** — nothing to retry (no failed platforms).
- **500 Internal Server Error**

Retry reuses the media snapshot stored from the original upload. There is no need to send the file again.

## Related

- [Unpublish](./unpublish-post.md)
- [Edit a published post](./edit-post.md)
- [Upload status](./upload-status.md)


---
# Save a Pinterest Pin
URL: https://docs.upload-post.com/api/save-pin

# Save a Pinterest Pin

Save an existing Pinterest pin onto one of the connected account's boards (repin).

## Endpoint

```
POST /api/uploadposts/posts/save
```

## Headers

| Name          | Value                    | Description                     |
| :------------ | :----------------------- | :------------------------------ |
| Authorization | Apikey your-api-key-here | Your API key for authentication |
| Content-Type  | application/json         | Request body format             |

## Body Parameters (JSON)

| Name | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `platform` | String | Yes | Must be `pinterest`. |
| `user` | String | Yes | Profile username. |
| `post_id` | String | Yes | Pin ID to save. |
| `pinterest_board_id` | String | Yes | Board to save the pin to. |
| `pinterest_board_section_id` | String | No | Optional board section. |

## Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/posts/save \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "pinterest",
    "user": "my-profile",
    "post_id": "1234567890",
    "pinterest_board_id": "board-id",
    "pinterest_board_section_id": "optional-section"
  }'
```

## Successful Response (`200 OK`)

```json
{
  "success": true,
  "post_id": "1234567890",
  "url": "https://www.pinterest.com/pin/1234567890/",
  "board_id": "board-id",
  "board_section_id": "optional-section",
  "parent_pin_id": "1234567890"
}
```

## Related

- [Pinterest boards](./get-pinterest-boards.md)
- [Unpublish](./unpublish-post.md)


---
# Manage Scheduled Posts
URL: https://docs.upload-post.com/api/schedule-posts

# Manage Scheduled Posts

Schedule your uploads in advance and keep full control over them with our job management endpoints. This page covers how to list and cancel scheduled jobs created via the `scheduled_date` parameter.

---

## List Scheduled Posts

| |  |
|---|---|
| **Endpoint** | `GET /api/uploadposts/schedule` |
| **Authentication** | **Required**. Either an Apikey (`Authorization: Apikey <token>`) or a white-label profile JWT (`Authorization: Bearer <profile_jwt>`). |
| **Query Params** | All optional &mdash; see below. |

### Query Parameters

| Param | Type | Description |
|-------|------|-------------|
| `profile_username` | `string` | Return only the jobs of this profile. |
| `from` | `string` | ISO-8601 lower bound on `scheduled_date`, **inclusive**. |
| `to` | `string` | ISO-8601 upper bound on `scheduled_date`, **exclusive**. |
| `limit` | `integer` | Page size. Omit to return every matching job. |
| `offset` | `integer` | Number of jobs to skip, ordered by `scheduled_date` ascending. Defaults to `0`. |

Malformed `from` / `to` / `limit` / `offset` values are ignored rather than rejected.

:::note White-label profile JWTs are scoped automatically
When you authenticate with a profile JWT, results are restricted to that profile and the `profile_username` parameter is ignored. You cannot widen the scope past the profile the token was issued for.
:::

```bash
curl "https://api.upload-post.com/api/uploadposts/schedule?profile_username=my_profile&from=2024-12-01T00:00:00Z&to=2025-01-01T00:00:00Z&limit=20" \
  -H "Authorization: Apikey YOUR_API_KEY"
```

### Success Response `200 OK`

Returns an **object** containing the page of jobs plus its pagination metadata:

```json
{
  "scheduled_posts": [
    {
      "job_id": "a1b2c3d4e5f67890a1b2c3d4e5f67890",
      "scheduled_date": "2024-12-25T10:30:00Z",
      "post_type": "video",
      "profile_username": "my_upload_post_profile",
      "title": "Merry Christmas!",
      "external_id": "clip-4821",
      "source_filename": "christmas_cut_v3.mp4",
      "platforms": ["tiktok", "instagram"],
      "platform_content": {
        "tiktok": { "title": "Merry Christmas!", "caption": "" },
        "instagram": { "title": "Merry Christmas!", "caption": "Happy holidays" }
      },
      "has_preview": true,
      "preview_url": null
    }
  ],
  "total": 42,
  "limit": 20,
  "offset": 0
}
```

| Field | Type | Description |
|-------|------|-------------|
| `scheduled_posts` | `array` | The requested page of scheduled-job objects. |
| `total` | `integer` | Total number of jobs matching your filters, ignoring `limit`/`offset`. Use it to build a pager. |
| `limit` | `integer \| null` | The `limit` that was applied; `null` when you didn't send one. |
| `offset` | `integer` | The `offset` that was applied. |

Each element of `scheduled_posts` contains:

| Field | Type | Description |
|-------|------|-------------|
| `job_id` | `string` | Unique identifier of the scheduled job. Required to edit or cancel it. |
| `scheduled_date` | `string` | ISO-8601 date/time when the post will go live. **Time is in UTC**. |
| `post_type` | `string` | One of `video`, `photo`, `text`, or `unknown`. |
| `profile_username` | `string` | Upload-Post profile that will publish the content. |
| `title` | `string` | Text shown in a calendar. For single-platform jobs this is the text that platform will actually publish. |
| `external_id` | `string \| null` | Your own identifier for this post, echoed back exactly as you sent it. See [Correlating posts with your own system](#correlating-posts-with-your-own-system). |
| `source_filename` | `string \| null` | Original file name of the uploaded media, when the job was created from a file or a URL. `null` for text posts. |
| `caption` / `description` | `string` | Generic caption and description stored with the job. |
| `platforms` | `array` | Platforms the job will publish to. |
| `platform_content` | `object` | Per-platform `title` / `caption`, falling back to the generic values. |
| `fields` | `object` | The editable per-platform and global fields, as accepted by the `PATCH` endpoint. |
| `has_cover` | `boolean` | Whether a cover image is stored for the job. |
| `cover_preview_url` | `string \| null` | Pre-stored cover URL, when one exists. |
| `has_preview` | `boolean` | Whether a previewable video/photo asset exists for the job. |
| `preview_url` | `null` | Always `null` &mdash; media URLs are signed on demand, see below. |
| `thumbnail_url` | `string \| null` | Stored thumbnail, when one exists. |
| `original_timezone` | `string \| null` | Timezone the job was originally scheduled in. |
| `original_scheduled_str` | `string \| null` | The original scheduled datetime string as submitted. |

### Correlating posts with your own system

If you schedule from your own catalogue, you likely need to map a scheduled job
back to the row it came from &mdash; to detect what you have already queued, or to
show status next to your own records.

Do **not** match on `title`. It is editable, both through the `PATCH` endpoint and
in the dashboard calendar, so a post that gets retitled stops matching and looks
like it was never scheduled.

Send your own identifier as `external_id` when you create the post, and read it
back here:

```bash
curl -X POST https://api.upload-post.com/api/upload \
  -H "Authorization: ApiKey YOUR_API_KEY" \
  -F "user=my_profile" \
  -F "platform[]=tiktok" \
  -F "title=Merry Christmas!" \
  -F "scheduled_date=2024-12-25T10:30:00Z" \
  -F "external_id=clip-4821" \
  -F "video=@christmas_cut_v3.mp4"
```

`external_id` accepts any string up to 255 characters. It can also be sent as an
`X-External-Id` header, which is handy when you cannot easily add form fields.
It is available on `/api/upload`, `/api/upload_photos` and `/api/upload_text`,
for both immediate and scheduled posts, and it is returned by
[Upload Status](./upload-status.md) and [Upload History](./upload-history.md) as
well as this endpoint.

`external_id` is a label, not a duplicate guard. Upload-Post stores and returns it but never enforces anything with it — reusing one does **not** block a publish, so a genuine repost still goes out. To collapse an accidental double-send, use an `Idempotency-Key` header (24 hours). The two are independent.

Signing one preview URL per job timed out large schedules, so `preview_url` is not populated here. When `has_preview` is `true`, fetch that job with `GET /api/uploadposts/schedule/<job_id>/preview`.

#### Error Responses

| Status | Reason |
|--------|--------|
| `401 Unauthorized` | Missing or invalid token. |

---

## Cancel a Scheduled Post

| | |
|---|---|
| **Endpoint** | `DELETE /api/uploadposts/schedule/<job_id>` |
| **Authentication** | **Required**. Either an Apikey (`Authorization: Apikey <token>`) or a white-label profile JWT (`Authorization: Bearer <profile_jwt>`). When authenticated with a profile JWT, the job must belong to that profile and the profile must have `readonly_calendar: false`. |
| **URL Param** | `job_id` &mdash; ID obtained from the list endpoint. |

### Success Response `200 OK`

```json
{
  "success": true,
  "message": "Job <job_id> cancelled and assets deleted.",
  "credits_refunded": 2
}
```

Scheduling reserves one monthly upload credit per platform at the moment the job is created. Cancelling a job that has not started yet returns those credits to your allowance; `credits_refunded` tells you how many (`0` for a job that was already publishing, and always `0` on unlimited plans, which reserve nothing).

### Error Responses

| Status | Body | Condition |
|--------|------|-----------|
| `401 Unauthorized` | &nbsp; | Invalid or missing token. |
| `404 Not Found` | `{ "success": false, "error": "Job not found" }` | The supplied `job_id` does not exist or doesn't belong to the authenticated user. |
| `500 Internal Server Error` | &nbsp; | Unexpected failure while cancelling the job or deleting its assets. |

---

## Edit a Scheduled Post

| | |
|---|---|
| **Endpoint** | `PATCH /api/uploadposts/schedule/<job_id>` |
| **Authentication** | **Required**. Either an Apikey (`Authorization: Apikey <token>`) or a white-label profile JWT (`Authorization: Bearer <profile_jwt>`). When authenticated with a profile JWT, the job must belong to that profile and the profile must have `readonly_calendar: false`. |
| **URL Param** | `job_id` &mdash; ID obtained from the list endpoint. |
| **Body** | JSON object with one or more of the fields below. |

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `scheduled_date` | `string` | No | ISO-8601 date/time, e.g., `"2025-10-05T10:30:00Z"`. Must be in the future and within 1 year. Interpreted as UTC unless `timezone` is provided. |
| `timezone` | `string` | No | IANA timezone identifier (e.g., `"Europe/Madrid"`, `"America/New_York"`). If provided, `scheduled_date` is interpreted in this timezone. Defaults to UTC if omitted. See [IANA Time Zone Database](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones). |
| `title` | `string` | No | New post title/caption. |
| `caption` | `string` | No | New caption/description. |

### Success Response `200 OK`

```json
{
  "success": true,
  "job_id": "a1b2c3d4e5f67890a1b2c3d4e5f67890",
  "scheduled_date": "2025-10-05T10:30:00Z",
  "title": "Updated title",
  "caption": "Updated caption"
}
```

### Error Responses

| Status | Body | Condition |
|--------|------|-----------|
| `400 Bad Request` | `{ "success": false, "error": "<reason>" }` | Invalid body; invalid/past date; job not editable; daily limit reached. |
| `401 Unauthorized` | &nbsp; | Invalid or missing token. |
| `403 Forbidden` | `{ "success": false, "error": "Forbidden" }` | The job does not belong to the authenticated user, or the profile JWT does not match the job's profile. |
| `403 Forbidden` | `{ "success": false, "message": "This calendar is read-only…", "error_code": "READONLY_CALENDAR" }` | Authenticated with a profile JWT for a profile that has `readonly_calendar: true`. |
| `404 Not Found` | `{ "success": false, "error": "Job not found" }` | The supplied `job_id` does not exist. |
| `500 Internal Server Error` | &nbsp; | Unexpected failure while editing the job. |

#### Example Request

```bash
curl -X PATCH "https://api.upload-post.com/api/uploadposts/schedule/JOB_ID" \
  -H "Content-Type: application/json" \
  -H "Authorization: Apikey <token>" \
  -d '{
    "scheduled_date": "2025-10-05T10:30:00",
    "timezone": "Europe/Madrid",
    "title": "Updated title",
    "caption": "Updated caption"
  }'
```

---

## See Also

* [Using `scheduled_date` when uploading content](./upload-video.md#common-parameters) – parameter description.
* [Upload Video](./upload-video.md), [Upload Photos](./upload-photo.md), [Upload Text](./upload-text.md) – endpoints that support scheduling.
* [Upload Status](./upload-status.md) – Check the execution status of scheduled posts using the `job_id`.


---
# Search TikTok Music
URL: https://docs.upload-post.com/api/search-tiktok-music

# Search TikTok Music

Searches TikTok's **Commercial Music Library (CML)** by song title or artist and
returns the same track objects as
[Get TikTok Trending Music](./get-tiktok-music.md). The `id` of a track is what
you pass as `tiktok_music_id` when uploading.

:::info What is actually searched
TikTok does not expose a music search endpoint: the only catalogue it publishes
is the trending chart for a given genre, country and period (100 tracks per
combination). Upload-Post caches those charts and runs the text match over them,
so this endpoint searches **the trending charts, not TikTok's entire catalogue**.
A song that is not trending in any chart you have loaded will not be found.
:::

-   **Method:** `GET`
-   **Endpoint:** `/api/uploadposts/tiktok/music/search`
-   **Authentication:**
    -   **API Key** in the `Authorization` header.
        -   `Authorization: Apikey <YOUR_API_KEY>`

### Query Parameters

| Parameter | Type | Required | Description | Default |
| :--- | :--- | :--- | :--- | :--- |
| `profile` | string | Yes | The profile's `username`. Must have a TikTok account connected. | - |
| `q` | string | No | Text to match against track titles and artists. Case- and accent-insensitive (`arvore` matches `Árvore da vida`); every word must match, so `bad bunny` does not return every track containing `bad`. Max 80 characters. Omit it to get the chart in trending order. | `""` |
| `genre` | string | No | Same genre values as the trending endpoint. Restricts the search to that genre. | `ALL` |
| `country_code` | string | No | ISO 3166-1 alpha-2 code choosing **which country's chart** is searched (e.g. `US`, `ES`, `GB`). A value that is not two letters falls back to `US`. | `US` |
| `date_range` | string | No | Chart window: `1DAY`, `7DAY`, `30DAY`, `90DAY`. | `7DAY` |
| `limit` | integer | No | Maximum tracks to return, capped at `100`. | `50` |

### Example Request

```bash
curl -G 'https://api.upload-post.com/api/uploadposts/tiktok/music/search' \
  -H 'Authorization: Apikey your-api-key-here' \
  -d 'profile=your_profile' \
  -d 'q=milky chance' \
  -d 'country_code=US' \
  -d 'limit=5'
```

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "query": "milky chance",
  "genre": "ALL",
  "country_code": "US",
  "date_range": "7DAY",
  "limit": 5,
  "total": 1,
  "tracks": [
    {
      "id": "7363314838511175697",
      "commercial_music_id": "7363314838511175000",
      "title": "Ok I Like It",
      "artist": "Milky Chance",
      "duration": 29,
      "rank": 3,
      "genres": ["POP"],
      "cover_url": "https://p16-sign.tiktokcdn.com/....jpeg",
      "preview_url": "https://sf16-cml.tiktokcdn.com/....mp3"
    }
  ],
  "catalog": {
    "tracks_indexed": 442,
    "genres_indexed": ["ALL", "ELECTRONIC", "JAZZ", "LATIN", "POP"],
    "cached": true
  }
}
```

The objects in `tracks` are identical to the ones the trending endpoint returns —
see [its field reference](./get-tiktok-music.md) — so a client can use both
endpoints interchangeably.

| Field | Description |
| :--- | :--- |
| `total` | How many tracks matched, before `limit` was applied. |
| `catalog.tracks_indexed` | How many tracks the search actually ran against. |
| `catalog.genres_indexed` | The genre charts currently loaded for this `country_code` / `date_range`. |
| `catalog.cached` | `false` when this request had to load a chart from TikTok, `true` when everything was already cached. |

### Ranking and coverage

- Matches on the **title** rank above matches on the **artist**; ties are broken
  by the track's position in the trending chart.
- The corpus grows as charts are loaded. Requesting a `genre` loads that genre's
  chart, so the more genres your integration browses for a given country and
  period, the more tracks a later search can match. `catalog.tracks_indexed`
  tells you how large the corpus was for the request you just made.
- Charts are refreshed a few times a day. If TikTok is unreachable and a cached
  chart exists, the cached one is served rather than failing the request.

### Error Responses

- `400 Bad Request` — `profile` was not sent, or `q` is longer than 80 characters.

```json
{ "success": false, "message": "q must be at most 80 characters" }
```

- `404 Not Found` — no profile with that username exists under your API key.
- `400 Bad Request` — the profile's TikTok connection lacks the `music`
  capability (`"error_code": "tiktok_reconnect_required"`). Reconnect the account.
- `409 Conflict` — the TikTok token expired and the account must be reconnected
  (`"reauth_required": true`).
- `502 Bad Gateway` — TikTok rejected the request and no cached chart was
  available to fall back on.

An unknown `genre`, `date_range` or `country_code` is **not** a 400: the value
falls back to `ALL`, `7DAY` and `US` respectively.

### Using a result on an upload

```bash
curl -X POST https://api.upload-post.com/api/upload \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user=your_profile' \
  -F 'platform[]=tiktok' \
  -F 'title=Golden hour 🌇' \
  -F 'video=@/path/to/video.mp4' \
  -F 'tiktok_music_id=7363314838511175697' \
  -F 'tiktok_music_volume=60' \
  -F 'tiktok_original_sound_volume=40'
```

Send the `id`, never `commercial_music_id` — TikTok rejects the latter on public
posts. See the full field reference in
[Upload Video → TikTok](./upload-video.md#tiktok).


---
# Suggestions (hashtags & keywords)
URL: https://docs.upload-post.com/api/suggestions

# Suggestions (hashtags & keywords)

What to tag a post with, and what people search around the word you are writing
about. Two answers to the same question — *what should this caption say to be
found* — so they are one endpoint with a `type`, not two URLs.

Like [`/audience`](./audience.md), [`/comments`](./comments.md) and
[`/post-analytics`](./get-analytics.md#get-apiuploadpostspost-analyticsplatform_post_id),
this is one endpoint per **question** with a `platform` telling it which network
to ask. Adding a network never changes your integration.

-   **Method:** `GET`
-   **Endpoint:** `/api/uploadposts/suggestions`
-   **Authentication:**
    -   **API Key** in the `Authorization` header.
        -   `Authorization: Apikey <YOUR_API_KEY>`

### Query Parameters

| Parameter | Type | Required | Description | Default |
| :--- | :--- | :--- | :--- | :--- |
| `platform` | string | Yes | The network to ask. Today only `tiktok` answers; anything else is a `400` naming the platforms that do. | - |
| `user` | string | Yes | The profile's `username`. Must have an account of that platform connected. | - |
| `type` | string | No | `hashtags` — the tags to pair with a keyword. `keywords` — what people search around it. | `hashtags` |
| `q` | string | Yes | The seed keyword. | - |
| `country_code` | string | No | ISO 3166-1 alpha-2 country to rank the suggestions for (e.g. `ES`, `US`, `MX`). Applies to `type=hashtags`. | the platform's own default |
| `language` | string | No | Language code for the suggestions (e.g. `es`, `en`). Applies to `type=hashtags`. | the platform's own default |

:::info `type=keywords` needs a reconnected account
On TikTok, search permission is granted **at the moment the account is
connected**, so only a **reconnected** account reports `trend_search` in its
[`capabilities`](./user-profiles.md#account-capabilities). `type=hashtags` works
on any recently connected account; `type=keywords` answers `400` with
`error_code: "tiktok_reconnect_required"` until its owner reconnects it from
[Manage Users](https://app.upload-post.com/manage-users).
:::

---

## `type=hashtags`

Given a keyword, the hashtags the platform suggests pairing with it and how many
times content carrying each one has been viewed.

```bash
curl -G 'https://api.upload-post.com/api/uploadposts/suggestions' \
  -H 'Authorization: Apikey your-api-key-here' \
  -d 'platform=tiktok' \
  -d 'user=your_profile' \
  -d 'type=hashtags' \
  --data-urlencode 'q=instant camera' \
  -d 'country_code=ES' \
  -d 'language=es'
```

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "platform": "tiktok",
  "type": "hashtags",
  "query": "instant camera",
  "hashtags": [
    { "name": "instantcamera", "view_count": 1284000000 },
    { "name": "polaroid", "view_count": 128970043215 },
    { "name": "analogphotography", "view_count": 742000000 },
    { "name": "camarainstantanea", "view_count": 31400000 }
  ]
}
```

| Field | Description |
| :--- | :--- |
| `query` | The keyword you asked for, echoed back. |
| `hashtags[].name` | The tag **without** the leading `#`. Add it yourself when writing the caption. |
| `hashtags[].view_count` | Lifetime views of content carrying that tag. It reaches **12 digits** on the big tags, so parse it as a 64-bit integer — a 32-bit one overflows. |

An empty `hashtags` array is a valid answer: there is nothing to suggest for that
keyword in that country/language combination. Try a broader keyword or drop
`country_code`.

---

## `type=keywords`

What people actually search around the word you give it. Use it before writing a
caption: it tells you which phrasing has demand, instead of guessing which of
five synonyms your audience types.

```bash
curl -G 'https://api.upload-post.com/api/uploadposts/suggestions' \
  -H 'Authorization: Apikey your-api-key-here' \
  -d 'platform=tiktok' \
  -d 'user=your_profile' \
  -d 'type=keywords' \
  --data-urlencode 'q=instant camera'
```

### Successful Response (`200 OK`)

```json
{
  "success": true,
  "platform": "tiktok",
  "type": "keywords",
  "query": "instant camera",
  "keywords": [
    { "keyword": "instant camera", "search_volume": 184000 },
    { "keyword": "instant camera film", "search_volume": 61200 },
    { "keyword": "best instant camera 2026", "search_volume": 24700 },
    { "keyword": "instant camera aesthetic", "search_volume": 9800 }
  ]
}
```

| Field | Description |
| :--- | :--- |
| `keywords` | The search terms, passed through as the platform returns them. Treat each object as an open shape: fields can be added at any time. |

---

### Error Responses

- `400 Bad Request` — the platform does not answer this question yet.

```json
{
  "success": false,
  "message": "'instagram' does not support suggestions yet. Supported: tiktok.",
  "error_code": "platform_not_supported"
}
```

- `400 Bad Request` — a missing `q` or `platform`, or a `type` outside the two
  values.

```json
{
  "success": false,
  "message": "type must be one of: hashtags, keywords",
  "error_code": "invalid_parameter"
}
```

- `400 Bad Request` — `type=keywords` on a connection that never granted search
  permission. Reconnect the account.

```json
{
  "success": false,
  "message": "This TikTok connection has not granted the permission this needs. Reconnect your TikTok account from Manage Users (https://app.upload-post.com/manage-users).",
  "error_code": "tiktok_reconnect_required"
}
```

- `404 Not Found` — no profile with that username exists under your API key
  (`error_code: "PROFILE_NOT_FOUND"`).
- `409 Conflict` — the token expired and the account must be reconnected
  (`"reauth_required": true`).
- `502 Bad Gateway` — the platform rejected the lookup; its message is returned
  verbatim in `message`.

### Additional Notes

- The two types compose: run `type=keywords` to pick the phrasing people search
  for, then `type=hashtags` on the winning term to pick the tags that carry it.
- Suggestions are not personalised to the account. The connection is what
  authorises the call, not what shapes the answer.

---

## Related

- [Audience Insights](./audience.md) — who follows the account and when they are
  online.
- [Upload Video](./upload-video.md) — where the caption you are writing ends up.
- [Character limits](../resources/character-limits.md) — how much caption each
  platform accepts.


---
# Unpublish a Published Post
URL: https://docs.upload-post.com/api/unpublish-post

# Unpublish a Published Post

Delete a post that was already published to a platform. This removes the live post from the target network.

## Platform support

| Platform        | Deletion |
| :-------------- | :------: |
| Facebook        |    ✅    |
| YouTube         |    ✅    |
| X               |    ✅    |
| LinkedIn        |    ✅    |
| Pinterest       |    ✅    |
| Bluesky         |    ✅    |
| Google Business |    ✅    |
| Discord         |    ✅    |
| Telegram        |    ✅    |
| Mastodon        |    ✅    |
| WordPress       |    ✅    |
| Threads         |    ❌    |
| Instagram       |    ❌    |
| TikTok          |    ❌    |

Instagram and TikTok do not support deletion via API. Threads returns `400` `error_code: "platform_not_supported"` (`threads_delete` is not granted). For Bluesky, `post_id` is the `at://` URI or a bsky.app URL. For Google Business, `post_id` is the full `accounts/{a}/locations/{l}/localPosts/{p}` name returned on upload.

## Endpoint

```
POST /api/uploadposts/posts/unpublish
```

## Headers

| Name          | Value                    | Description                     |
| :------------ | :----------------------- | :------------------------------ |
| Authorization | Apikey your-api-key-here | Your API key for authentication |
| Content-Type  | application/json         | Request body format             |

## Body Parameters (JSON)

| Name       | Type   | Required | Description                                                                                          |
| :--------- | :----- | :------- | :--------------------------------------------------------------------------------------------------- |
| `platform` | String | Yes      | One of `facebook`, `youtube`, `x`, `linkedin`, `pinterest`, `bluesky`, `google_business`, plus credential channels that support delete. `threads` returns `platform_not_supported`. |
| `user`     | String | Yes      | Profile username (as configured in Upload-Post).                                                     |
| `post_id`  | String | Yes      | The published post's ID on the target platform.                                                      |

## Example Request

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/posts/unpublish \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "platform": "youtube",
    "user": "my-profile",
    "post_id": "dQw4w9WgXcQ"
  }'
```

## Successful Response (`200 OK`)

```json
{
  "success": true,
  "message": "Post deleted successfully"
}
```

## Error Responses

- **400 Bad Request** — missing fields, or an unsupported platform (e.g. `instagram`, `tiktok`, `threads`).
- **403 Forbidden** — the connected account is not authorized to delete the post.
- **404 Not Found** — no post found for the given `post_id`.
- **500 Internal Server Error**

## Related

- [Retry a failed upload](./retry-post.md)
- [Edit a published post](./edit-post.md)
- [YouTube thumbnail](./youtube-thumbnail.md)


---
# Upload Document
URL: https://docs.upload-post.com/api/upload-document

# Upload Document

Upload documents (PDF, PPT, PPTX, DOC, DOCX) to LinkedIn as native document posts. Documents are displayed as carousels/viewers on LinkedIn.

### Endpoint

```http
POST /api/upload_document
```

### Headers

| Name | Value | Description |
|------|-------|-------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

### Parameters

| Name | Type | Required | Description |
|------|------|----------|-------------|
| user | String | Yes | User identifier (profile name) |
| platform[] | Array | Yes | Must be `["linkedin"]` - only LinkedIn supports document uploads |
| document | File/URL | Yes | The document file to upload (file upload or URL). Supported formats: PDF, PPT, PPTX, DOC, DOCX |
| title | String | Yes | Document title (displayed on the post). LinkedIn caps this field at **400 characters**, counting each emoji as two and each line break as one. Longer titles are trimmed to 400 characters with an ellipsis; put the full text in `description`, which has a much larger limit. |
| description | String | No | Post commentary/text that appears above the document |
| visibility | String | No | Visibility setting: "PUBLIC", "CONNECTIONS", "LOGGED_IN", or "CONTAINER". Default: "PUBLIC" |
| target_linkedin_page_id | String | No | LinkedIn organization/page ID to post to a company page instead of personal profile |
| first_comment | String | No | Automatically post a first comment after publishing the document. |
| linkedin_first_comment | String | No | Platform-specific first comment override. Takes priority over `first_comment`. |
| scheduled_date | String | No | ISO 8601 date/time to publish the document later (e.g. `2026-09-01T10:00:00Z`). Must be in the future. When present the request returns `202` with a `job_id` and the document is published by the scheduler. |
| timezone | String | No | IANA timezone (e.g. `Europe/Madrid`) used to interpret `scheduled_date` when it has no offset. Defaults to UTC. |

### Document Requirements

| Requirement | Value |
|-------------|-------|
| Supported Formats | PDF, PPT, PPTX, DOC, DOCX |
| Maximum File Size | 100 MB |
| Maximum Pages | 300 pages |

### Example Request (File Upload)

```bash
curl -X POST "https://api.upload-post.com/api/upload_document" \
  -H "Authorization: Apikey your-api-key-here" \
  -F "user=your_profile" \
  -F "platform[]=linkedin" \
  -F "document=@/path/to/document.pdf" \
  -F "title=My Presentation" \
  -F "description=Check out this presentation on our latest product updates!"
```

### Example Request (URL)

```bash
curl -X POST "https://api.upload-post.com/api/upload_document" \
  -H "Authorization: Apikey your-api-key-here" \
  -F "user=your_profile" \
  -F "platform[]=linkedin" \
  -F "document=https://example.com/document.pdf" \
  -F "title=My Presentation" \
  -F "description=Check out this presentation!"
```

### Example Request (Company Page)

```bash
curl -X POST "https://api.upload-post.com/api/upload_document" \
  -H "Authorization: Apikey your-api-key-here" \
  -F "user=your_profile" \
  -F "platform[]=linkedin" \
  -F "document=@/path/to/document.pdf" \
  -F "title=Company Update Q4 2024" \
  -F "description=Our quarterly report is now available." \
  -F "target_linkedin_page_id=12345678" \
  -F "visibility=PUBLIC"
```

### Example Request (Scheduled)

```bash
curl -X POST https://api.upload-post.com/api/upload_document \
  -H "Authorization: Apikey your-api-key-here" \
  -F "user=my_profile" \
  -F "platform[]=linkedin" \
  -F "document=@/path/to/deck.pdf" \
  -F "title=Q3 Results" \
  -F "description=Our quarterly results, in 12 slides." \
  -F "scheduled_date=2026-09-01T10:00:00" \
  -F "timezone=Europe/Madrid"
```

Scheduled documents answer with `202 Accepted` and a `job_id`; manage them with the
[scheduled posts](./schedule-posts.md) endpoints like any other scheduled post.

### Success Response

```json
{
  "success": true,
  "message": "Document uploaded successfully",
  "request_id": "abc123def456",
  "results": {
    "linkedin": {
      "success": true,
      "document_urn": "urn:li:document:1234567890",
      "post_id": "urn:li:activity:7654321098765432",
      "url": "https://www.linkedin.com/feed/update/urn:li:activity:7654321098765432/",
      "platform": "linkedin",
      "content_type": "document",
      "file_size": 1048576,
      "filename": "document.pdf"
    }
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Document upload failed: Your title is too long for LinkedIn. LinkedIn limits the media title of a video or document post to 400 characters, counting each emoji as two.",
  "request_id": "abc123def456",
  "results": {
    "linkedin": {
      "success": false,
      "error": "Your title is too long for LinkedIn. LinkedIn limits the media title of a video or document post to 400 characters, counting each emoji as two.",
      "error_source": "platform",
      "linkedin_status": 422
    }
  }
}
```

#### Error status codes

The HTTP status tells you what to do next, so you do not have to parse the message:

| Status | Meaning | What to do |
|--------|---------|------------|
| `400` | The request must change. Either we rejected it (`error_source: "client"` — unreadable URL, unsupported file, bad `target_linkedin_page_id`) or LinkedIn did (`error_source: "platform"` with a 4xx in `linkedin_status` — title too long, missing permission, connection needing reconnection). | Fix the request or reconnect the account. Retrying as-is will fail again. |
| `502` | LinkedIn is down or timed out. The failed platform result carries `"retryable": true`. | Retry later with the same payload. |
| `500` | A failure we could not attribute — ours. | Retry; if it persists, contact support with the `request_id`. |

A dead LinkedIn connection answers `400`, not `401`. A `401` from this API always
means your Upload-Post credentials failed, never that a social account needs
reconnecting.

### How Documents Appear on LinkedIn

When you upload a document:

1. **Native Viewer**: LinkedIn displays the document in a native carousel/viewer format
2. **Page Navigation**: Users can swipe or click through pages
3. **Preview**: LinkedIn generates thumbnail previews for each page
4. **Download**: Depending on visibility settings, users may be able to download the document

### Platform Limitations

| Platform | Document Support |
|----------|------------------|
| LinkedIn | Yes (native carousel/viewer) |
| Facebook | No |
| Instagram | No |
| TikTok | No |
| X (Twitter) | No |
| YouTube | No |
| Pinterest | No |
| Threads | No |
| Bluesky | No |
| Reddit | No |

### Notes

- Document processing may take a few seconds on LinkedIn's side before the post becomes fully visible
- The document title appears as the post's media title, and LinkedIn limits it to 400 characters. Titles above that are trimmed and the response carries a `warnings` entry saying so — send the long text in `description` instead. See [Character Limits](../resources/character-limits).
- The description/commentary appears as the post text above the document
- For company pages, ensure the authenticated LinkedIn account has admin access to the page
- LinkedIn may compress or optimize documents for viewing

### Related Endpoints

- [Upload Video](./upload-video) - Upload videos to multiple platforms
- [Upload Photo](./upload-photo) - Upload images to multiple platforms
- [Upload Text](./upload-text) - Post text-only content
- [Get LinkedIn Pages](./get-linkedin-pages) - List available LinkedIn pages for your account


---
# Upload History
URL: https://docs.upload-post.com/api/upload-history

# Upload History

Retrieve a paginated list of your past uploads across platforms.

## Endpoint

```http
GET /api/uploadposts/history
```

## Headers

| Name | Value | Description |
|------|-------|-------------|
| Authorization | Apikey your-api-key | Required.|

## Query Parameters

| Name | Type | Required | Default | Allowed | Description |
|------|------|----------|---------|---------|-------------|
| page | Integer | No | 1 | >= 1 | Page number |
| limit | Integer | No | 10 | 10, 20, 50, 100 | Page size |
| platform | String | No | — | `tiktok`, `instagram`, `youtube`, `facebook`, `linkedin`, `x`, `threads`, `reddit`, `pinterest`, `bluesky`, `google_business`, `discord`, `telegram`, … | Only rows for this platform |
| status | String | No | — | `success`, `failed` | Only successful or only failed rows |
| profile_username | String | No | — | any profile `username` | Only rows of this profile (`profile` is accepted as an alias) |
| request_id | String | No | — | ≤ 200 chars | **Exact match.** All rows produced by one upload request (one per platform) |
| job_id | String | No | — | ≤ 200 chars | **Exact match.** All rows produced by one scheduled/async job |
| external_id | String | No | — | ≤ 200 chars | **Exact match.** Rows of the post you tagged with this `external_id` when creating it |
| start | Date | No | — | `YYYY-MM-DD` or ISO 8601 | Start of the date range (requires `end`) |
| end | Date | No | — | `YYYY-MM-DD` or ISO 8601 | End of the date range (requires `start`; max 2 months) |

Filters combine with AND. The exact-match ids are scoped to your account, so
looking up an id that belongs to another account returns an empty page, not an
error. The same filters apply to the `in_progress` list.

### Look up one request / job / external id

```bash
# every platform row of one upload request
curl "https://api.upload-post.com/api/uploadposts/history?request_id=req_123&limit=10" \
  -H "Authorization: Apikey your-api-key"

# the post you tagged with your own id
curl "https://api.upload-post.com/api/uploadposts/history?external_id=cms-post-8841&limit=10" \
  -H "Authorization: Apikey your-api-key"

# one profile's failed uploads this week
curl "https://api.upload-post.com/api/uploadposts/history?profile_username=acme&status=failed&start=2026-08-18&end=2026-08-25&limit=50" \
  -H "Authorization: Apikey your-api-key"
```

## Responses

- 200 OK
  - `history`: array of history items (most recent first)
  - `in_progress`: uploads still running (last 24h), same filters applied
  - `total`: total number of records for the user
  - `page`: requested page
  - `limit`: requested limit
- 400 Bad Request: `{ "error": "Invalid page" }`, `{ "error": "Invalid limit" }`, `{ "error": "Invalid request_id" }` (id longer than 200 chars or with control characters), or a date-range error
- 401 Unauthorized: `{ "success": false, "message": "Invalid or expired token" }`
- 500 Internal Server Error: `{ "error": "Failed to retrieve upload history", "details": "..." }`

## History Item Schema

Typical fields (not all fields are guaranteed on every record):

- `user_email`: string
- `profile_username`: string
- `platform`: string (e.g., `tiktok`, `instagram`, `linkedin`, `youtube`, `facebook`, `x`, `threads`, `pinterest`, `google_business`, `discord`, `telegram`)
- `media_type`: string (`video` | `photo` | `text`)
- `upload_timestamp`: string (ISO-8601)
- `success`: boolean
- `platform_post_id`: string | array | null
- `post_url`: string | null (present when `success` is true)
- `error_message`: string | null
- `media_size_bytes`: number | null
- `post_title`: string | null
- `post_caption`: string | null
- `is_async`: boolean | null
- `job_id`: string | null (present when the upload originated from a scheduled job)
- `dashboard`: any | null
- `video_was_transcoded`: boolean | null
- `changes`: object | null
- `prevalidation_metadata`: object | null
- `request_id`: string | null
- `external_id`: string | null (the identifier you supplied when creating the post; absent when you didn't send one)
- `request_total_platforms`: number | null
- `fallback_to_inbox`: boolean (TikTok only; `true` when the post succeeded but was delivered to the account's inbox/drafts because of TikTok's daily active-user cap — it is **not live** until published from the TikTok app. Absent on records from before August 2026; for those, a video inbox delivery usually shows `post_url: "Video sent to Inbox (No Public URL)"`, while photo inbox deliveries have `post_url: null`. See the [reached_active_user_cap guide](/guides/reached-active-user-cap-error).)

Scheduled posts include `job_id` on the history item — that is how you match the job to the publish record. To match a row in your own system, send `external_id` when you create the post and read it back here; `post_title` is editable and a bad key. See [Scheduled Posts](./schedule-posts#correlating-posts-with-your-own-system).

## Example Request

```bash
curl -X GET "https://api.upload-post.com/api/uploadposts/history?page=1&limit=20" \
  -H "Authorization: Apikey your-api-key"
```

## Example 200 Response (truncated)

```json
{
  "history": [
    {
      "user_email": "user@example.com",
      "profile_username": "profile_username",
      "platform": "instagram",
      "media_type": "video",
      "upload_timestamp": "2025-09-04T10:22:33.123Z",
      "success": true,
      "platform_post_id": "1789654321",
      "post_url": "https://instagram.com/p/abc123",
      "media_size_bytes": 12345678,
      "post_title": "Title",
      "post_caption": "Description",
      "is_async": false,
      "job_id": "a1b2c3d4e5f67890a1b2c3d4e5f67890",
      "dashboard": true,
      "request_id": "req_123",
      "request_total_platforms": 3
    }
  ],
  "total": 42,
  "page": 1,
  "limit": 20
}
```

## See also

- [Upload Status](./upload-status)
- [Manage Scheduled Posts](./schedule-posts)
- [Upload Text](./upload-text)
- [Upload Video](./upload-video)
- [Upload Photos](./upload-photo)


---
# Upload Photos
URL: https://docs.upload-post.com/api/upload-photo

# Upload Photos

Upload photos (and mixed media for supported platforms) to various social media platforms using this endpoint.

### Endpoint

```
POST /api/upload_photos
```

### Headers

| Name          | Value                      | Description                              |
|---------------|----------------------------|------------------------------------------|
| Authorization | Apikey your-api-key-here   | Your API key for authentication          |
| Idempotency-Key | unique-string | Optional. Prevents duplicate uploads if the same request is retried (e.g., after a timeout). Can also be sent as `X-Idempotency-Key` or `X-Request-Id`. When provided, if a matching upload job already exists, the API returns the existing job instead of creating a duplicate. |

### Common Parameters

| Name       | Type   | Required | Description                                                                                   |
|------------|--------|----------|-----------------------------------------------------------------------------------------------|
| user       | String | Yes      | User identifier                                                                               |
| platform[] | Array  | Yes      | Platform(s) to upload to. Supported values: tiktok, instagram, linkedin, facebook, x, threads, pinterest, bluesky, reddit, discord, telegram, google_business, mastodon, lemmy, wordpress |
| photos[]   | Array  | Yes      | Array of files to upload. Accepts **photos** (jpg, png, etc.). <br /> **Note:** You can also include **videos** (mp4, mov, etc.) ONLY for **Instagram** and **Threads** mixed carousels. |
| title      | String | Conditional | Default title/caption of the post. **Required** for Reddit. Optional for all other platforms (TikTok, Instagram, Facebook, LinkedIn, X, Threads, Bluesky, Pinterest). |
| description    | String | No       | Optional extended text used on TikTok photo descriptions, LinkedIn commentary, Facebook descriptions, Pinterest notes, and Reddit bodies. Ignored elsewhere. |
| request_id | String | No | Client-provided request identifier. If omitted, the server generates one. Returned in every response and used to track the upload via [Upload Status](./upload-status). Useful when `async_upload=true` and the HTTP response might be lost (e.g., timeout). Can also be sent as an `X-Request-Id` header. |
| scheduled_date | String (ISO-8601) | No | Optional date/time (ISO-8601) to schedule publishing, e.g., "2024-12-31T23:45:00Z". Must be in the future (≤ 365 days). Omit for immediate upload. |
| timezone | String (IANA) | No | Optional timezone identifier (e.g., "Europe/Madrid", "America/New_York"). If provided, `scheduled_date` is interpreted in this timezone. Defaults to UTC if omitted. See [IANA Time Zone Database](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones) for valid values. |
| external_id | String | No | Your own identifier for this post (max 255 chars), echoed back by [Scheduled Posts](./schedule-posts), [Upload Status](./upload-status) and [Upload History](./upload-history). Use it to map a post back to a record in your own system instead of matching on `title`, which is editable. Can also be sent as an `X-External-Id` header. It is a label only — reusing one never blocks a publish; for that, send an `Idempotency-Key` header. |
| async_upload  | Boolean | No      | If `true`, the request returns immediately with a `request_id` and processes in the background. See [Upload Status](./upload-status). |
| add_to_queue  | Boolean | No      | If `true`, automatically schedules the post to your next available queue slot. Cannot be used with `scheduled_date`. See [Queue System](./queue-system). |
| max_posts_per_slot | Integer | No | Override the profile's max posts per slot setting for this request. Only used when `add_to_queue=true`. See [Queue System](./queue-system). |
| first_comment | String | No       | Automatically post a first comment after publishing. Supported on Instagram, Facebook, Threads, Bluesky, X, YouTube, LinkedIn, and TikTok. On X (Twitter) and Threads, this creates a reply to the main post. For X threads, the comment is posted as a reply to the last tweet in the thread. On YouTube, it posts as a top-level comment on the video. On TikTok it needs the `comments` capability and never fails the publish — see the note below. Reddit posting is currently unavailable (503 `reddit_unavailable`). |
| first_comment_media[] | File(s) | No | Image files to attach to the first comment as inline images. Reddit-only, and Reddit posting is currently unavailable (503 `reddit_unavailable`). Not available for scheduled or queued posts. |

Uploads that take more than 59 seconds switch to async even if `async_upload` is `false` — poll [Upload Status](./upload-status) with the `request_id`.

`scheduled_date` returns **202 Accepted** and a `job_id`. Use that id on [Upload Status](./upload-status) while it runs and on [Upload History](./upload-history) after it publishes.

### Platform-Specific First Comments

The `first_comment` parameter serves as a fallback. To set a custom first comment for a particular platform, use the optional `[platform]_first_comment` parameter. If provided, it will override the main `first_comment` for that platform.

**Example Optional Parameters:**

*   `instagram_first_comment`: "Follow for more content! #photography"
*   `facebook_first_comment`: "Let me know your thoughts in the comments!"
*   `x_first_comment`: "Thread incoming! 🧵"
*   `threads_first_comment`: "First comment on Threads!"
*   `reddit_first_comment`: "Source in the comments."
*   `bluesky_first_comment`: "More details in the replies."
*   `linkedin_first_comment`: "Source article in the comments."
*   `tiktok_first_comment`: "Full tutorial in the link in bio."

:::info TikTok first comments need a reconnected account
`first_comment` / `tiktok_first_comment` work on TikTok, on video and photo
posts alike, but only on a connection that reports the `comments`
[capability](./user-profiles.md#account-capabilities) — TikTok grants comment
permission at the moment the account is connected, so the account owner has to
have reconnected it from
[Manage Users](https://app.upload-post.com/manage-users).

**The first comment never fails the publish.** By the time it is attempted the
post is already live, so anything that goes wrong comes back as a plain-string
entry in the response's `warnings` array while `success` stays `true`. A failure
returned as an error would make your retry logic republish the post and you
would end up with it twice.

```json
{
    "success": true,
    "post_id": "7401234567890123456",
    "warnings": [
        "A first comment on TikTok needs a reconnected TikTok account. The post was published without it. Reconnect your TikTok account from Manage Users (https://app.upload-post.com/manage-users)."
    ]
}
```

When it does go through, the response carries `"first_comment_posted": true`.

It is also skipped — with a warning, never silently — when there is nothing to
comment on: a post saved to the account's **drafts** is not published yet, and
occasionally TikTok has not returned the post id when the publish confirms.
:::

Instagram and Threads accept videos inside `photos[]` for mixed carousels. Every other platform (Facebook, TikTok, LinkedIn, X, Pinterest) rejects videos on this endpoint — use [Upload Video](./upload-video).

### Platform-Specific Titles

The `title` parameter serves as a fallback. To set a custom title for a particular platform, use the optional `[platform]_title` parameter. If provided, it will override the main `title` for that platform.

**Example Optional Parameters:**

*   `instagram_title`: "Check out my latest reel on Instagram! #reels"
*   `facebook_title`: "Excited to share this new video with my Facebook friends and family."
*   `tiktok_title`: "New TikTok video just dropped! 🔥"
*   `linkedin_title`: "A professional insight on the latest industry trends, discussed in this video."
*   `x_title`: "New video out now! 📢"

### Platform-Specific Parameters

### LinkedIn

| Name                    | Type   | Required | Description                                                    | Default     |
|-------------------------|--------|----------|----------------------------------------------------------------|-------------|
| linkedin_title          | String | No       | Specific title for the LinkedIn post. Fallbacks to `title`.    | `title`     |
| linkedin_description or description | String | No | Sent as the post commentary. If omitted, we reuse `title`. | `title`     |
| visibility              | String | No       | `PUBLIC`, `CONNECTIONS`, `LOGGED_IN`, `CONTAINER`. Aliases: `linkedin_visibility`, `linkedinVisibility`. | PUBLIC      |
| target_linkedin_page_id | String | No       | LinkedIn page ID to upload photos to an organization         | "107579166" |
| linkedin_alt_text | String, JSON list, or repeated field | No | Alt text per image. Defaults to `title`. | `title` |
| linkedin_disable_reshare | Boolean | No | When `true`, disable reshare. | false |

### Facebook

| Name             | Type   | Required | Description                                                       | Default |
|------------------|--------|----------|-------------------------------------------------------------------|---------|
| facebook_title   | String | No       | Specific title for the Facebook post. Fallbacks to `title`.       | `title` |
| facebook_page_id | String | Yes      | Facebook Page ID where the photos will be posted                  | -       |
| facebook_media_type | String | No | Type of media ("POSTS" or "STORIES") | "POSTS" |
| facebook_alt_text | String, `facebook_alt_text[]`, or JSON array | No | Custom alt text (`alt_text_custom`) per photo. | - |
| facebook_place_id | String | No | Facebook place ID (`place`). | - |
| facebook_targeting | JSON object | No | Page targeting. | - |
| facebook_feed_targeting | JSON object | No | Feed targeting. | - |
| facebook_no_story | Boolean | No | When `true`, do not publish a story from this post. | - |
| facebook_secret | Boolean | No | Unpublished/secret photo. | - |

Connecting Facebook only links the account; it does **not** pick a destination Page. Meta only allows posting to Pages, not personal profiles. The caption is applied only to the first photo. The Page should be associated with the personal profile, not only a Business Portfolio.

Pass `facebook_page_id` on every upload. If you omit it and exactly one Page is connected, that Page is used. If several are connected, the API returns `available_pages`. Look up ids with [Get Facebook Pages](./get-facebook-pages.md).

### X (Twitter)

:::warning URLs are stripped from every X post
Upload-Post removes every URL that X would turn into a clickable link from
the caption, title, and `first_comment` before sending the tweet — schemed
URLs (`https://`, `http://`, `ftp://`), `www.` hosts, shorteners (`t.co`,
`bit.ly`, …), bare hostnames with a path (`example.com/foo`), and IPs with a
path.

Obfuscated forms (`example[.]com`, `hxxp://`, unicode dots) are **not**
stripped because X does not parse them as links — they display as plain text
and are billed at the normal `$0.015` rate.

**Why:** X charges `$0.200` per post containing a URL vs `$0.015` without —
13× more. See the
[Character Limits page](../resources/character-limits.md#x-twitter-character-limits)
for details.
:::

| Name                        | Type    | Required | Description                                                                                                                           | Default     |
|-----------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------|-------------|
| x_title                      | String  | No       | Specific title for the tweet. Fallbacks to `title`.                                                                                   | `title`     |
| x_long_text_as_post          | Boolean | No       | When `true`, publishes long text as a single post. Otherwise, creates a thread.                                                      | `false`     |
| x_thread_image_layout        | String  | No       | Comma-separated list of how many images to attach to each tweet in the thread. Each value must be 0-4, and the total must equal the number of images. Use `0` for a text-only tweet in the thread (e.g., `"0,4"` makes the first tweet text-only and attaches all 4 images to the second tweet). Example: `"4,4"` puts 4 images in each of 2 tweets; `"2,3,1"` puts 2 in the first, 3 in the second, 1 in the third. If omitted and more than 4 images are provided, defaults to auto-chunking into groups of 4. | auto        |
| reply_settings               | String  | No       | Controls who can reply to the tweet ("following", "mentionedUsers", "subscribers", "verified")                                       | -           |
| geo_place_id                 | String  | No       | Place ID for adding geographic location to the tweet                                                                                  | -           |
| nullcast                     | Boolean | No       | Whether to publish without broadcasting (promotional/promoted-only posts)                                                             | `false`     |
| made_with_ai                 | Boolean | No       | Disclose that the post contains AI-generated media. Also accepted via the cross-platform `is_ai_generated` alias.                     | `false`     |
| for_super_followers_only     | Boolean | No       | Tweet exclusive for super followers                                                                                                   | `false`     |
| community_id                 | String  | No       | Community ID for posting to specific communities                                                                                      | -           |
| share_with_followers         | Boolean | No       | Share community post with followers                                                                                                   | `false`     |
| direct_message_deep_link     | String  | No       | Link to take the conversation from public timeline to private Direct Message                                                         | -           |
| tagged_user_ids              | Array   | No       | Array of user IDs to tag in the photos (max 10 users)                                                                                 | []          |
| reply_to_id                  | String  | No       | ID of the tweet to reply to. Creates a reply to the specified tweet.                                                                  | -           |
| exclude_reply_user_ids       | Array   | No       | Array of user IDs to exclude from replying to this tweet. Requires `reply_to_id`.                                                    | []          |
| x_alt_text | String, `x_alt_text[]`, or JSON array | No | Alt text per image, max 1,000 characters. | - |
| x_paid_partnership | Boolean | No | Paid partnership flag (applied to the first tweet). | false |

Note: For Twitter uploads, specify the platform as "x" in the platform[] array.

`quote_tweet_id` cannot be used here: X treats media and quote tweets as mutually exclusive. Quote via [Upload Text](./upload-text.md) and share the image as a separate tweet.

`reply_to_id` can return **HTTP 403** on X's Pay-Per-Use tier if the account has not engaged with that author. Reconnecting does not fix it.

X allows 4 images per tweet. More than 4 become a thread (up to 4 images each). Control the split with `x_thread_image_layout`.

The global `description` field is ignored for X photo uploads.

#### How X (Twitter) Thread Creation Works (Advanced Logic)
**Note:** The following describes the default thread creation logic. To override this and post long text as a single post, set the `x_long_text_as_post` parameter to `true`.

The system is engineered to create well-formatted, natural-looking threads on X (formerly Twitter). Instead of simply splitting text at every line break, it intelligently groups paragraphs to create more readable tweets.

Here's the step-by-step logic:

**Intelligent Paragraph Grouping (Primary Method):**

The function first identifies distinct paragraphs (any text separated by a blank line).
It then combines as many of these paragraphs as possible into a single tweet, filling it up to the 280-character limit without exceeding it. The double newline (`\n\n`) between combined paragraphs is preserved for formatting.
This results in fewer, more substantial tweets that flow naturally, just as if a person had written them.

**Handling Exceptionally Long Paragraphs:**

If a single paragraph is, by itself, longer than the 280-character limit, a more granular splitting logic is automatically triggered for that paragraph only:

- **Split by Line Break:** The system first attempts to break the paragraph down by its individual line breaks (`\n`).
- **Split by Word:** If any of those single lines are still too long, it will split them by words as a final resort.

**Media Attachment:**

Images are distributed across tweets according to the `x_thread_image_layout` parameter. If not specified and more than 4 images are provided, they are automatically distributed in groups of 4. Text parts and image chunks are interleaved across the thread tweets.

### TikTok

| Name                  | Type    | Required | Description                                                                                 | Default |
|-----------------------|---------|----------|---------------------------------------------------------------------------------------------|---------|
| tiktok_title          | String  | No       | Specific title for the TikTok post (max 90 characters). Fallbacks to `title`.               | `title` |
| post_mode             | String  | No      | `DIRECT_POST` publishes immediately. `MEDIA_UPLOAD` sends the media to TikTok drafts (same public idea as `tiktok_upload_to_draft=true`). Either field works on any TikTok account; do not pick a different one for Business vs standard. | DIRECT_POST       |
| disable_inbox_fallback | Boolean | No     | When `true`, a `DIRECT_POST` upload that hits TikTok's daily active-user cap returns the `reached_active_user_cap` error instead of being delivered to the inbox as a draft. Use this if your integration has its own retry/reschedule logic — inbox drafts cannot be deleted via TikTok's API. See the [Active User Cap guide](/guides/reached-active-user-cap-error). Only applies to TikTok connections that still publish through the older route (their `capabilities` list includes `inbox_fallback`); a reconnected account is not subject to the cap, so the field has nothing to do and is ignored. | false             |
| privacy_level         | String  | No | Accepted values: `PUBLIC_TO_EVERYONE`, `MUTUAL_FOLLOW_FRIENDS`, `FOLLOWER_OF_CREATOR`, `SELF_ONLY`. TikTok requires a value on photo posts; Upload-Post sends `PUBLIC_TO_EVERYONE` when you omit it. TikTok decides per account which levels are available — a private account has no `PUBLIC_TO_EVERYONE`. | PUBLIC_TO_EVERYONE |
| auto_add_music        | Boolean | No       | Automatically add background music to photos                                                | false   |
| disable_comment       | Boolean | No       | Disable comments on the post                                                                | false   |
| brand_content_toggle | Boolean| No       | Set to `true` for paid partnerships that promote third-party brands.        | false   |
| brand_organic_toggle | Boolean| No       | Set to `true` when promoting the creator's own business.                    | false   |
| photo_cover_index     | Integer | No       | Index (starting at 0) of the photo to use as the cover/thumbnail for the TikTok photo post  | 0       |
| tiktok_music_id       | String  | No       | Commercial Music Library track id from [Get TikTok Music](./get-tiktok-music.md) or [Search TikTok Music](./search-tiktok-music.md) (the `id` field, not `commercial_music_id`). Photo posts take the id alone — the volume and trim fields are video-only. Needs the `music` capability. | -       |
| tiktok_location_id    | String  | No       | TikTok place id to tag. Get it from [Get TikTok Locations](./get-tiktok-locations.md). Requires `tiktok_location_name`. Needs the `location` capability. | -       |
| tiktok_location_name  | String  | Conditional | Display name of the place. **Required** whenever `tiktok_location_id` is sent. Needs the `location` capability. | -       |
| tiktok_is_ai_generated | Boolean | No      | Declares the content as AI-generated. Aliases: `is_ai_generated`, `is_aigc`.                 | false   |
| tiktok_upload_to_draft | Boolean | No      | Alias of `post_mode=MEDIA_UPLOAD`. `true` is the same draft mode — prefer `post_mode`. Also accepted as `upload_to_draft`. | false   |
| tiktok_description or description    | String  | No       | For photo posts, used as description inside `post_info` (max 4,000 characters). | `title`   |

Draft mode (`post_mode=MEDIA_UPLOAD`, alias `tiktok_upload_to_draft=true`) sends the photos to TikTok drafts to finish in the app. Same field on Business and standard accounts. Photo drafts may keep `title` / `description`; video drafts do not.

#### TikTok specifics

:::info Check the connection's capabilities before sending optional fields
The optional `tiktok_*` fields above are available on connections that declare
the matching capability. Call
[`GET /api/uploadposts/users`](./user-profiles.md#account-capabilities) and look
at the `capabilities` list on the profile's TikTok account (`music`, `location`,
`cover_image`, `cover_timestamp`, `draft`, `photo_privacy`, `video_privacy`,
`inbox_fallback`, `profile_analytics`).

If your connection does not declare a capability, the post is **not rejected**:
it publishes normally and the response includes a `warnings` entry naming the
field that was ignored. Reconnect the TikTok account from
[Manage Users](https://app.upload-post.com/manage-users) to enable it — your API
calls stay exactly the same.
:::

- **`privacy_level` works the same on photos and on videos** (capabilities
  `photo_privacy` / `video_privacy`), with one difference: TikTok *requires* a
  value on photo posts, so Upload-Post sends `PUBLIC_TO_EVERYONE` when you omit
  it, while a video with no `privacy_level` keeps the account's own default.
  Which levels an account may use is decided by TikTok per account (a private
  account has no `PUBLIC_TO_EVERYONE`) — ask
  [`GET /api/uploadposts/tiktok/settings`](./get-tiktok-settings.md) and read
  `privacy_level_options`. This matters more on photos than on videos: the
  default Upload-Post has to send is exactly the one a private account cannot
  use, so those accounts must set `privacy_level` explicitly. Asking for a level
  the account does not have is refused with
  `error_code: "tiktok_privacy_unavailable"` before anything reaches TikTok.
- **Media requirements:** up to **35 images**, JPG/JPEG/WebP, ≤ 20 MB each, up to
  1080 × 1920. Title up to 90 characters, description up to 4,000 characters and
  30 mentions.
- **Music on photo posts:** `tiktok_music_id` is accepted (capability `music`),
  but only the track id — `tiktok_music_volume`, `tiktok_music_start`,
  `tiktok_music_end` and `tiktok_original_sound_volume` are **video-only** and
  are ignored here. To let TikTok pick a track instead, use `auto_add_music`.
- **Rate limits:** 6 posts per minute and 15 posts per day per TikTok account.

When TikTok's daily active-user cap is hit, a `DIRECT_POST` is retried as `MEDIA_UPLOAD` instead of failing. The response still has `success: true`, but the post is in the inbox (`"fallback_to_inbox": true` — photo ids do not use the video `v_inbox_file` prefix). Business-plan accounts get `error_code: "reached_active_user_cap"` instead. Opt out per upload with `disable_inbox_fallback=true`. See the [Active User Cap guide](/guides/reached-active-user-cap-error).

### Instagram

| Name | Type | Required | Description | Default |
|------|------|----------|-------------|---------|
| instagram_title | String | No       | Specific title for the Instagram post. Fallbacks to `title`. | `title` |
| media_type | String | No | Type of media ("IMAGE" or "STORIES"). Automatically handles CAROUSEL/REELS logic if mixed media is detected. | "IMAGE" |
| collaborators | String | No | Comma-separated list of collaborator usernames. | - |
| user_tags | String | No | Users to tag on the photo. **Photo posts require x/y coordinates** — see below. | - |
| location_id | String | No | Instagram location ID (Facebook Page location / place id). Applied to single images and to the carousel as a whole; ignored for Stories, which Instagram does not allow to be tagged with a location. | - |
| is_ai_generated | Boolean | No | Set to `true` to self-disclose the media as AI-generated. Instagram shows an "AI info" label under the account name. For carousels the label applies to the whole post. It cannot be added or removed after publishing. | false |
| instagram_alt_text | String or JSON list | No | Alt text per image, max 1,000 characters per item. | - |

#### Note on Instagram `user_tags` for photo posts

Instagram's Graph API requires `x` and `y` coordinates (floats between `0.0` and `1.0`, marking the tag position on the image) whenever you tag a user on a **photo** post. Username-only tags are silently dropped by Instagram on photos — the post still publishes, but without the tag.

Send `user_tags` as a JSON-encoded array of objects:

```json
"user_tags": "[{\"username\":\"glassdojo\",\"x\":0.5,\"y\":0.5}]"
```

Tag multiple users by adding more objects, each with its own coordinates:

```json
"user_tags": "[{\"username\":\"user1\",\"x\":0.3,\"y\":0.4},{\"username\":\"user2\",\"x\":0.7,\"y\":0.6}]"
```

For carousels, the same tags are applied to every image in the carousel.

> **Reels/videos** accept the simpler comma-separated form (`"@user1, user2"`) because Instagram does not require coordinates for video tags. See [Upload Video](./upload-video.md#instagram).

The global `description` field is ignored for Instagram uploads (title serves as caption).

### Threads

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| threads_title | String | No | Specific title for the Threads post. Fallbacks to `title`. | `title` |
| threads_thread_media_layout | String | No | Comma-separated list of how many media items to include in each Threads post. Each value must be 0-20, and the total must equal the number of files. Use `0` for a text-only post in the thread (e.g., `"0,5"` makes the first post text-only and attaches all 5 media items to the second post). Example: `"5,5"` splits 10 items into 2 posts of 5 each; `"3,4,3"` splits 10 items into 3 posts. If omitted and more than 20 items are provided, defaults to auto-chunking. | auto |
| threads_topic_tag | String | No | A topic tag for the post (1-50 characters). Cannot contain periods (.) or ampersands (&). One tag per post. Helps increase reach. | - |
| threads_alt_text | String or `threads_alt_text[]` | No | Alt text per image, max 1,000 characters. | - |
| threads_reply_control | String | No | Who can reply: `everyone`, `accounts_you_follow`, `mentioned_only`, `parent_post_author_only`, `followers_only` (alias `followers`). | - |
| threads_reply_to_id | String | No | Numeric post ID to reply to. | - |
| threads_quote_post_id | String | No | Numeric post ID to quote. | - |

> **More than 20 items:** Threads supports a maximum of 20 media items per post (carousel). If you provide more than 20 items, the API will automatically create multiple posts. Use `threads_thread_media_layout` to control exactly how media items are distributed across posts.

The global `description` field is ignored for Threads photo uploads.

### Pinterest

| Name                 | Type   | Required | Description                                  | Default |
|----------------------|--------|----------|----------------------------------------------|---------|
| pinterest_title      | String | No       | Specific title for the Pinterest Pin. Fallbacks to `title`. | `title` |
| pinterest_description or description | String | No | Populates the Pin description. If omitted, we reuse `title`. | `title` |
| pinterest_board_id   | String | Yes      | Pinterest board ID to publish the photo to.  | -       |
| pinterest_alt_text   | String | No       | Alt text for the image.                      | -       |
| pinterest_link       | String | No       | Destination link for the photo Pin.          | -       |
| pinterest_board_section_id | String | No | Board section ID. | - |
| pinterest_ai_disclosures | CSV, JSON, or list | No | `AI_MODIFIED`, `SYNTHETIC_PERFORMER`. | - |
| pinterest_carousel_titles[] | String | No | Per-slide titles for carousels (2–5 photos). | - |
| pinterest_carousel_descriptions[] | String | No | Per-slide descriptions for carousels. | - |
| pinterest_carousel_links[] | String | No | Per-slide links for carousels. | - |
| pinterest_carousel_index | Integer | No | 0-based carousel start index. | - |

### Bluesky

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| bluesky_title | String | No | Specific text for the Bluesky post. Fallbacks to `title`. | `title` |
| bluesky_alt_text | String, JSON array, or `\|\|`-separated | No | Alt text per image. | - |
| bluesky_langs | String | No | Up to 3 BCP-47 codes, comma-separated (e.g. `en,es`). | - |
| bluesky_labels | String | No | Comma list of `porn`, `sexual`, `nudity`, `graphic-media`. | - |
| bluesky_gallery | Boolean | No | When `true`, publish up to 20 images as `app.bsky.embed.gallery`. Default is the first 4. | false |
| bluesky_threadgate | String | No | Who can reply: `everyone`, `nobody`, `mention`, `following`, `followers`, `list:<at://uri>` (comma-separated). Alias: `bluesky_reply_settings`. | everyone |
| bluesky_postgate | String | No | `disable_quotes` (or `true`) to block quotes. Alias: `bluesky_quote_settings`. | quotes allowed |
| bluesky_quote_uri | String | No | Post URL or `at://` URI to quote. Aliases: `bluesky_quote_id`, `bluesky_quote_url`. | - |

Note: Bluesky supports up to 4 images per post by default. If you send more without gallery mode (for example a 10-image Instagram carousel that also targets Bluesky), the first 4 are published and the response includes a `warnings` entry; the Bluesky post is not failed. Use `bluesky_gallery=true` to publish up to 20 images as a gallery.

### Reddit

:::warning Reddit posting is currently unavailable
Uploads with `platform[]=reddit` return **HTTP 503** with `error_code: "reddit_unavailable"`. Do not include `reddit` in `platform[]` until posting is restored. OAuth connect and comments with `platform=reddit` return the same error.
:::

```json
{
  "success": false,
  "error_code": "reddit_unavailable",
  "error": "Reddit posting is currently unavailable."
}
```

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| reddit_title | String | No | Specific title for the Reddit post. Fallbacks to `title`. Unused while Reddit is unavailable. | `title` |
| subreddit | String | Yes | Name of the subreddit to post to (without "r/"). Unused while Reddit is unavailable. | - |
| flair_id | String | No | ID of the flair to apply to the post. Unused while Reddit is unavailable. | - |

### Discord

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| discord_title | String | No | Caption sent alongside the image(s). Fallbacks to `title`. | `title` |
| discord_alt_text | String or `\|`-separated | No | Alt text per image. | - |
| discord_thread_id | String | No | Existing thread ID. | - |
| discord_thread_name | String | No | Create a thread (max 100 characters). | - |
| discord_flags | Integer | No | Bitfield: `4` SUPPRESS_EMBEDS, `4096` SUPPRESS_NOTIFICATIONS. | - |

Note: Discord posts the images as message attachments to the channel behind the connected webhook (see [Connecting Discord](../guides/connecting-accounts.md#connecting-discord-manual-credentials)). Up to **10 images** are supported per message; the optional caption is limited to 2000 characters.

### Telegram

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| telegram_title | String | No | Caption sent alongside the image(s). Fallbacks to `title`. | `title` |
| telegram_parse_mode | String | No | `MarkdownV2` or `HTML`. | - |
| telegram_has_spoiler | Boolean | No | Mark media as spoiler. | - |
| telegram_disable_notification | Boolean | No | Send silently. | - |
| telegram_protect_content | Boolean | No | Disallow forwarding/saving. | - |

Note: Your connected bot delivers the image(s) to the configured chat/channel (see [Connecting Telegram](../guides/connecting-accounts.md#connecting-telegram-manual-credentials)). A single photo is sent via `sendPhoto`; multiple photos are sent as an album via `sendMediaGroup`. The optional caption is limited to **1024 characters**.

### Mastodon

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| mastodon_title | String | No | Caption for the Mastodon post. Fallbacks to `title`. | `title` |
| mastodon_visibility | String | No | `public`, `unlisted`, `private`, or `direct`. | - |
| mastodon_sensitive | Boolean | No | Mark as sensitive. | - |
| mastodon_spoiler_text | String | No | Content warning. | - |
| mastodon_alt_text | String (`\|`-separated) | No | Alt text per image. | - |
| mastodon_language | String | No | BCP-47 language code. | - |

Note: Each image is uploaded to your instance and attached to the status (see [Connecting Mastodon](../guides/connecting-accounts.md#connecting-mastodon-manual-credentials)). Up to **4 images** per post.

### Lemmy

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| lemmy_title | String | No | Title of the Lemmy post (required by Lemmy, min 3 characters). Fallbacks to `title`. | `title` |
| lemmy_nsfw | Boolean | No | Mark as NSFW. | - |
| lemmy_alt_text | String | No | Alt text for the image. | - |
| lemmy_language_id | Integer | No | Lemmy language ID. | - |

Note: The first image is uploaded to the instance's image host and set as the post URL (see [Connecting Lemmy](../guides/connecting-accounts.md#connecting-lemmy-manual-credentials)). A single image per post; Lemmy posts always require a title.

### WordPress

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| wordpress_title | String | No | Post title. Fallbacks to `title`. | `title` |
| wordpress_status | String | No | `publish`, `draft`, `pending`, or `future`. | - |
| wordpress_alt_text | String | No | Featured-image alt text. | - |
| wordpress_excerpt | String | No | Post excerpt. | - |
| wordpress_slug | String | No | Post slug. | - |

Note: Images are uploaded to the WordPress media library; the first becomes the post's featured image (see [Connecting WordPress](../guides/connecting-accounts.md#connecting-wordpress-manual-credentials)).

### Google Business Profile

| Name | Type | Required | Description | Default |
|------|------|----------|-------------|---------|
| gbp_location_id | String | No* | The location to post to. Use [Get Google Business Locations](./get-google-business-locations.md) to list available locations. | Auto |
| gbp_topic_type | String | No | Post type: `STANDARD` (default), `EVENT`, or `OFFER`. | `STANDARD` |
| gbp_cta_type | String | No | Call-to-action button: `BOOK`, `ORDER`, `SHOP`, `LEARN_MORE`, `SIGN_UP`, `CALL`. Alias: `cta_type`. Other spellings (`cta_action_type`, `gbp_cta_action_type`) are accepted too, but the canonical name is `gbp_cta_type`. An unknown value is rejected with `400` instead of publishing the post without its button. | - |
| gbp_cta_url | String | Conditional | URL the button opens. Required for every type except `CALL`, which dials the location's phone number and must not carry a URL. Missing URL → `400`. Alias: `cta_url`. | - |
| gbp_post_type | String | No | Set to `MEDIA` (also accepts `PHOTO` or `GALLERY`) to publish the photo to the location's **Media/Gallery** tab instead of creating a Local Post. See [Publishing to the Media/Gallery tab](#publishing-to-the-mediagallery-tab). | - |
| gbp_upload_to_gallery | Boolean | No | Alternative to `gbp_post_type`. Set to `true` for the same Media/Gallery behaviour. | `false` |
| gbp_media_category | String | No | Category assigned to the uploaded photo in the Media tab. Alias: `media_category`. Only used when publishing to the Media/Gallery tab. | `ADDITIONAL` |

If `gbp_location_id` is omitted and the account has exactly one location, that location is used. If several are connected, the API asks you to pick one.

**Event parameters** (when `gbp_topic_type` is `EVENT`):

| Name | Type | Required | Description |
|------|------|----------|-------------|
| gbp_event_title | String | Yes | Title of the event. |
| gbp_event_start_date | String | Yes | Start date in `YYYY-MM-DD` format. |
| gbp_event_start_time | String | No | Start time in `HH:MM` format (24h). |
| gbp_event_end_date | String | Yes | End date in `YYYY-MM-DD` format. |
| gbp_event_end_time | String | No | End time in `HH:MM` format (24h). |

**Offer parameters** (when `gbp_topic_type` is `OFFER`):

| Name | Type | Required | Description |
|------|------|----------|-------------|
| gbp_coupon_code | String | No | Coupon or promo code. Alias of `gbp_offer_coupon` (old name wins if both are sent). |
| gbp_redeem_url | String | No | URL where the offer can be redeemed. Alias of `gbp_offer_redeem_url`. |
| gbp_terms | String | No | Terms and conditions of the offer. Alias of `gbp_offer_terms`. |
| gbp_language_code | String | No | BCP-47 language. Default `"en"`. |

#### Publishing to the Media/Gallery tab

By default, publishing to Google Business creates a **Local Post** — an entry in the location's "Updates" tab. You can instead push a photo straight to the location's **Media/Gallery** tab (the photo gallery customers see on the business listing).

To do so, send one of these fields on the upload request:

| Name | Type | Description |
|------|------|-------------|
| gbp_post_type | String | `MEDIA`, `PHOTO` or `GALLERY` — all three select the Media/Gallery tab. |
| gbp_upload_to_gallery | Boolean | `true` — equivalent to `gbp_post_type=MEDIA`. |

If `gbp_post_type` is omitted, or is anything other than `MEDIA` / `PHOTO` / `GALLERY`, the request stays a Local Post.

**Media categories**

`gbp_media_category` (alias: `media_category`) sets the category the photo is filed under in the Media tab. It defaults to `ADDITIONAL`.

| Allowed values |
|----------------|
| `COVER`, `PROFILE`, `LOGO`, `EXTERIOR`, `INTERIOR`, `PRODUCT`, `AT_WORK`, `FOOD_AND_DRINK`, `MENU`, `COMMON_AREA`, `ROOMS`, `TEAMS`, `ADDITIONAL` |

**Example Request:**

<Tabs groupId="lang">
<TabItem value="curl" label="cURL">

```bash
curl -X POST https://api.upload-post.com/api/upload_photos \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user=my-profile' \
  -F 'platform[]=google_business' \
  -F 'photos[]=@/path/to/storefront.jpg' \
  -F 'gbp_location_id=accounts/123456789/locations/222222222' \
  -F 'gbp_post_type=MEDIA' \
  -F 'gbp_media_category=EXTERIOR'
```

</TabItem>
<TabItem value="python" label="Python">

```python
import requests

response = requests.post(
    "https://api.upload-post.com/api/upload_photos",
    headers={"Authorization": "Apikey your-api-key-here"},
    files=[("photos[]", open("/path/to/storefront.jpg", "rb"))],
    data={
        "user": "my-profile",
        "platform[]": "google_business",
        "gbp_location_id": "accounts/123456789/locations/222222222",
        "gbp_post_type": "MEDIA",
        "gbp_media_category": "EXTERIOR",
    },
)
print(response.json())
```

</TabItem>
<TabItem value="js" label="JavaScript">

```javascript

const form = new FormData();
form.append("photos[]", new Blob([fs.readFileSync("/path/to/storefront.jpg")]), "storefront.jpg");
form.append("user", "my-profile");
form.append("platform[]", "google_business");
form.append("gbp_location_id", "accounts/123456789/locations/222222222");
form.append("gbp_post_type", "MEDIA");
form.append("gbp_media_category", "EXTERIOR");

const response = await fetch("https://api.upload-post.com/api/upload_photos", {
  method: "POST",
  headers: { Authorization: "Apikey your-api-key-here" },
  body: form,
});
console.log(await response.json());
```

</TabItem>
</Tabs>

**Success Response (200 OK):**

```json
{
  "success": true,
  "post_id": "accounts/123456789/locations/222222222/media/AF1QipN...",
  "url": "https://lh3.googleusercontent.com/...",
  "platform": "google_business",
  "post_type": "media",
  "media_category": "EXTERIOR",
  "media_format": "PHOTO"
}
```

| Field | Description |
|-------|-------------|
| `post_id` | The Google Business media resource name (`accounts/../locations/../media/..`). |
| `url` | The `googleUrl` of the uploaded media. |
| `post_type` | `"media"` — confirms the photo went to the Media/Gallery tab rather than to a Local Post. |
| `media_category` | The category the photo was filed under. |
| `media_format` | `"PHOTO"`. |

**Error Responses:**

*   `400 Bad Request` — invalid category:

    ```json
    {
      "success": false,
      "message": "Invalid gbp_media_category 'BANNER'. Allowed values: COVER, PROFILE, LOGO, EXTERIOR, INTERIOR, PRODUCT, AT_WORK, FOOD_AND_DRINK, MENU, COMMON_AREA, ROOMS, TEAMS, ADDITIONAL",
      "error_code": "INVALID_MEDIA_CATEGORY"
    }
    ```

*   `400 Bad Request` — gallery request sent without any media:

    ```json
    {
      "success": false,
      "message": "A photo is required to upload to the Google Business media gallery",
      "error_code": "MEDIA_REQUIRED"
    }
    ```

    This is why a Media/Gallery request must carry an image. The same fields are accepted on `/api/upload` and `/api/upload_text`, but a request with no media attached will fail with `MEDIA_REQUIRED`.

### Example Requests

### Upload Photo and Video to Instagram (Carousel)

<Tabs groupId="lang">
<TabItem value="curl" label="cURL">

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'photos[]=@/path/to/image.jpg' \
  -F 'photos[]=@/path/to/video.mp4' \
  -F 'user="test"' \
  -F 'platform[]=instagram' \
  -F 'title="My Mixed Carousel"' \
  -X POST https://api.upload-post.com/api/upload_photos
```

</TabItem>
<TabItem value="python" label="Python">

```python
import requests

response = requests.post(
    "https://api.upload-post.com/api/upload_photos",
    headers={"Authorization": "Apikey your-api-key-here"},
    files=[
        ("photos[]", open("/path/to/image.jpg", "rb")),
        ("photos[]", open("/path/to/video.mp4", "rb")),
    ],
    data={
        "user": "test",
        "platform[]": "instagram",
        "title": "My Mixed Carousel",
    },
)
print(response.json())
```

</TabItem>
<TabItem value="js" label="JavaScript">

```javascript

const form = new FormData();
form.append("photos[]", new Blob([fs.readFileSync("/path/to/image.jpg")]), "image.jpg");
form.append("photos[]", new Blob([fs.readFileSync("/path/to/video.mp4")]), "video.mp4");
form.append("user", "test");
form.append("platform[]", "instagram");
form.append("title", "My Mixed Carousel");

const response = await fetch("https://api.upload-post.com/api/upload_photos", {
  method: "POST",
  headers: { Authorization: "Apikey your-api-key-here" },
  body: form,
});
console.log(await response.json());
```

</TabItem>
</Tabs>

### Upload Photos to Facebook

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'photos[]=@/path/to/image1.jpg' \
  -F 'photos[]=@/path/to/image2.jpg' \
  -F 'user="test"' \
  -F 'platform[]=facebook' \
  -F 'facebook_page_id="123456789"' \
  -F 'title="My Photo Album"' \
  -X POST https://api.upload-post.com/api/upload_photos
```

### Upload Photo to Reddit

Reddit posting is currently unavailable. A request with `platform[]=reddit` returns **HTTP 503**:

```json
{
  "success": false,
  "error_code": "reddit_unavailable",
  "error": "Reddit posting is currently unavailable."
}
```

### Responses

- 200 OK (synchronous, finished fast)

```json
{
  "success": true,
  "results": {
    "instagram": { "success": true, "url": "https://instagram.com/p/...", "photos_were_processed": true, "changes_per_image": [ {} ] },
    "tiktok":   { "success": true, "url": "https://www.tiktok.com/@…/photo/…" }
  },
  "usage": { "count": 13, "limit": 100, "last_reset": "..." }
}
```

- 200 OK (asynchronous/background started or sync→background fallback)

```json
{
  "success": true,
  "message": "Upload initiated successfully in background.",
  "request_id": "1a2b3c4d5e...",
  "total_platforms": 2
}
```

- 202 Accepted (scheduled)

```json
{
  "success": true,
  "job_id": "scheduler_job_456",
  "scheduled_date": "2025-09-22T10:00:00Z"
}
```

- 400 Bad Request
  - Missing `user`, `platform[]`, Pinterest without `pinterest_board_id`, invalid platforms, invalid `scheduled_date`. Reddit-only requests return **503** `reddit_unavailable`.

- 401 Unauthorized: `{ "success": false, "message": "Invalid or expired token" }`

- 403 Forbidden (plan restrictions)

- 404 Not Found (e.g., user not found)

- 429 Too Many Requests (monthly limit exceeded; includes current usage)

```json
{
  "success": false,
  "message": "This upload would exceed your monthly limit.",
  "usage": { "count": 10, "limit": 10, "last_reset": "..." }
}
```

- 500 Internal Server Error: `{ "success": false, "error": "Detailed error message" }`

Notes
- When async or when sync falls back to background, use `GET /api/uploadposts/status?request_id={request_id}` to poll progress.
- Per-platform results may include fields like `url`, `post_id(s)`, and platform-specific metadata or `error`. A result with `skipped: true` means the profile has no account for that platform (see [Unconnected platforms](#unconnected-platforms)).

### Unconnected platforms

Each profile only has accounts for the platforms you connected to it. When a request lists several platforms and the profile has **no account for some of them**, the upload is **not rejected**: the connected platforms are published normally and each unconnected one comes back in `results` as *skipped* (nothing is posted there and it is not counted as a failure in your dashboard):

```json
{
  "success": true,
  "results": {
    "instagram": { "success": true, "url": "https://instagram.com/p/..." },
    "linkedin": {
      "success": false,
      "skipped": true,
      "skip_reason": "profile_platform_not_configured",
      "error": "Profile creator has no Linkedin account configured",
      "error_code": "profile_platform_mapping_invalid",
      "failure_stage": "profile_platform_validation"
    }
  }
}
```

For asynchronous uploads the same per-platform entry is returned by [`GET /api/uploadposts/status`](./upload-status.md), and skipped platforms count as finished, so `completed` reaches `total` as soon as the connected platforms are done.

If **none** of the requested platforms is connected to the profile, the request is rejected with `400`:

```json
{
  "success": false,
  "message": "None of the requested platforms are valid for profile \"creator\". Profile creator has no Linkedin account configured",
  "invalid_platforms": { "linkedin": "Profile creator has no Linkedin account configured" }
}
```

**Scheduled posts** (`scheduled_date`) keep **every** requested platform, connected or not, and are validated again when the job runs. Connect the missing account before the scheduled time and the post will be published there too; otherwise that platform is reported as skipped at execution time.


---
# Upload Status
URL: https://docs.upload-post.com/api/upload-status

# Upload Status

Check the status of asynchronous uploads initiated with `async_upload=true` or scheduled posts.

## Endpoint

```http
GET /api/uploadposts/status?request_id=yourrequestid
GET /api/uploadposts/status?job_id=yourjobid
```

## Headers

| Name | Value | Description |
|------|-------|-------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |

## Query Parameters

| Name | Type | Required | Description |
|------|------|----------|-------------|
| request_id | String | Conditional | The request identifier returned by the upload endpoints when `async_upload=true`. Use for async uploads. You can also provide your own `request_id` when submitting the upload (via form field or `X-Request-Id` header) so you can poll status even if the upload response is lost due to a timeout. |
| job_id | String | Conditional | The job identifier returned by scheduled posts. Use for posts with `scheduled_date`. |

**Note:** At least one of `request_id` or `job_id` must be provided.

The response echoes back the `external_id` you supplied when creating the post
(`null` when you didn't send one), so you can match a status poll to a record in
your own system. See
[Scheduled Posts](./schedule-posts#correlating-posts-with-your-own-system).

## Behavior

- **Async uploads**: When you submit an upload request with `async_upload=true`, the API returns immediately with a `request_id`. Use this to retrieve aggregated progress and results.
- **Scheduled posts**: When you schedule a post with `scheduled_date`, the API returns a `job_id`. Use this to check the status after the scheduled time.
- The top-level `status` field may be one of:
  - `pending`: The request has been accepted but no platform results have been recorded yet. For scheduled posts, this means the job has not executed yet.
  - `queued`: The upload is queued and waiting for a worker to begin processing.
  - `processing`: At least one platform is actively being processed while others are still queued or pending.
  - `in_progress`: Some platform results have been recorded but not all.
  - `completed`: All platforms have finished successfully.
  - `failed`: All platforms have failed, or no activity has been recorded for over 1 hour.
  - `not_found`: No upload request was found with the provided ID (returned with HTTP 404).

- Individual platform results may have their own status:
  - `queued`: The platform upload is waiting to be processed.
  - `processing`: The platform upload is currently being processed.
  - `completed`: The platform upload finished successfully.
  - `failed`: The platform upload failed permanently.
  - `retryable`: The platform upload failed but is eligible for automatic retry.
  - `skipped`: The platform was never attempted because the profile has no account connected for it. The entry also carries `skipped: true` and `skip_reason: "profile_platform_not_configured"`. Skipped platforms count towards `completed`, so a request whose connected platforms all finished is reported as `completed` even if one platform was skipped. See [Unconnected platforms](./upload-video.md#unconnected-platforms).

- **TikTok inbox fallback**: a TikTok result may include `"fallback_to_inbox": true`. The upload succeeded, but because of TikTok's daily active-user cap it was delivered to the account's **inbox/drafts** instead of being published live — the owner must open the TikTok app to publish it. Video drafts usually show `post_url: "Video sent to Inbox (No Public URL)"`; photo drafts have `post_url: null`, so rely on the flag. See the [reached_active_user_cap guide](/guides/reached-active-user-cap-error) for details.

## Example Request

**For async uploads:**

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  "https://api.upload-post.com/api/uploadposts/status?request_id=<REQUEST_ID>"
```

**For scheduled posts:**

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  "https://api.upload-post.com/api/uploadposts/status?job_id=<JOB_ID>"
```

## Example Response

**For async uploads (request_id) — in progress:**

```json
{
  "request_id": "7b2c2f5e-1234-4a6f-9f1d-a1b2c3d4e5f6",
  "external_id": "clip-4821",
  "status": "in_progress",
  "completed": 1,
  "total": 2,
  "results": [
    {
      "platform": "x",
      "success": true,
      "message": "Queued",
      "upload_timestamp": "2025-01-01T12:34:56Z"
    }
  ],
  "last_update": "2025-01-01T12:34:56Z"
}
```

**For scheduled posts (job_id):**

```json
{
  "job_id": "job_abc123xyz",
  "status": "completed",
  "completed": 2,
  "total": 2,
  "results": [
    {
      "platform": "x",
      "success": true,
      "message": "Published",
      "upload_timestamp": "2025-01-15T14:00:00Z"
    },
    {
      "platform": "linkedin",
      "success": true,
      "message": "Published",
      "upload_timestamp": "2025-01-15T14:00:05Z"
    }
  ],
  "last_update": "2025-01-15T14:00:05Z"
}
```

**Failed upload:**

```json
{
  "request_id": "ae8e8d98-dead-40bd-a206-8bacc9efea84",
  "status": "failed",
  "message": "Upload appears to have failed (no activity for over 1 hour)",
  "completed": 0,
  "total": 1,
  "results": []
}
```

**Not found:**

```json
{
  "request_id": "nonexistent-id",
  "status": "not_found",
  "message": "No upload request found with this ID"
}
```

## Responses

| Status | Description |
|--------|-------------|
| 200 OK | Success. Response includes `request_id` or `job_id` depending on which parameter was used. |
| 400 Bad Request | Missing both `request_id` and `job_id`: `{"error":"request_id or job_id is required"}` |
| 401 Unauthorized | Invalid or expired token |
| 404 Not Found | No upload request found with the provided ID |
| 500 Internal Server Error | Server error with details |

## SDK Examples

### Python

```python
from upload_post import UploadPostClient

client = UploadPostClient(api_key="your-api-key-here")

# Check status of an async upload
status = client.get_status("request_id_from_upload")

# Check status of a scheduled or queued post
status = client.get_job_status("job_id_from_scheduled_post")
```

### JavaScript/Node.js

```javascript

const client = new UploadPost('your-api-key-here');

// Check status of an async upload
const status = await client.getStatus('request_id_from_upload');

// Check status of a scheduled or queued post
const jobStatus = await client.getJobStatus('job_id_from_scheduled_post');
```

## Polling Best Practices

The status endpoint uses internal caching. Polling faster than the cache refresh interval returns the same result and wastes your rate limit budget.

| Status | Cache TTL | Recommended poll interval |
|--------|-----------|--------------------------|
| `queued` / `pending` | 2 seconds | Every 5–10 seconds |
| `processing` | 3 seconds | Every 10 seconds |
| `completed` / `failed` | 5 minutes | Stop polling — result is final |

For high-volume integrations, consider using [webhooks](./webhooks) instead of polling — you'll receive an instant `POST` notification when the upload completes.

See the full [Rate Limits & Polling guide](../guides/rate-limits) for detailed recommendations.

## Related

- [Text uploads](./upload-text)
- [Video uploads](./upload-video)
- [Photo uploads](./upload-photo)
- [Schedule posts](./schedule-posts)
- [Rate Limits & Polling](../guides/rate-limits)


---
# Upload Text
URL: https://docs.upload-post.com/api/upload-text

# Upload Text

Upload text posts to various social media platforms using this endpoint.

**Note:** Currently, this endpoint supports X (Twitter), LinkedIn, Facebook, Threads, Reddit, Bluesky, Discord, Telegram, and Google Business Profile. More platforms will be added in future updates.

### Endpoint

```
POST /api/upload_text
```

### Headers

| Name          | Value                      | Description                              |
|---------------|----------------------------|------------------------------------------|
| Authorization | Apikey your-api-key-here   | Your API key for authentication          |
| Idempotency-Key | unique-string | Optional. Prevents duplicate uploads if the same request is retried (e.g., after a timeout). Can also be sent as `X-Idempotency-Key` or `X-Request-Id`. When provided, if a matching upload job already exists, the API returns the existing job instead of creating a duplicate. |

### Common Parameters

| Name          | Type   | Required | Description                                                                |
|---------------|--------|----------|----------------------------------------------------------------------------|
| user          | String | Yes      | User identifier                                                            |
| platform[]    | Array  | Yes      | Platform(s) to upload to. Supported values: linkedin, x, facebook, threads, reddit, bluesky, discord, telegram, google_business, slack, mastodon, nostr, lemmy, devto, hashnode, wordpress, whop, listmonk |
| title         | String | Yes      | Default text content for the post.                                         |
| description    | String | No       | Optional extended body used only on Reddit (becomes the post text). Ignored elsewhere. |
| request_id | String | No | Client-provided request identifier. If omitted, the server generates one. Returned in every response and used to track the upload via [Upload Status](./upload-status). Useful when `async_upload=true` and the HTTP response might be lost (e.g., timeout). Can also be sent as an `X-Request-Id` header. |
| scheduled_date | String (ISO-8601) | No | Optional date/time (ISO-8601) to schedule publishing, e.g., "2024-12-31T23:45:00Z". Must be in the future (≤ 365 days). Omit for immediate upload. |
| timezone | String (IANA) | No | Optional timezone identifier (e.g., "Europe/Madrid", "America/New_York"). If provided, `scheduled_date` is interpreted in this timezone. Defaults to UTC if omitted. See [IANA Time Zone Database](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones) for valid values. |
| external_id | String | No | Your own identifier for this post (max 255 chars), echoed back by [Scheduled Posts](./schedule-posts), [Upload Status](./upload-status) and [Upload History](./upload-history). Use it to map a post back to a record in your own system instead of matching on `title`, which is editable. Can also be sent as an `X-External-Id` header. It is a label only — reusing one never blocks a publish; for that, send an `Idempotency-Key` header. |
| async_upload  | Boolean | No      | If `true`, the request returns immediately with a `request_id` and processes in the background. See [Upload Status](./upload-status). |
| add_to_queue  | Boolean | No      | If `true`, automatically schedules the post to your next available queue slot. Cannot be used with `scheduled_date`. See [Queue System](./queue-system). |
| max_posts_per_slot | Integer | No | Override the profile's max posts per slot setting for this request. Only used when `add_to_queue=true`. See [Queue System](./queue-system). |
| first_comment | String | No       | Automatically post a first comment after publishing. Supported on Facebook, Threads, Bluesky, X, YouTube, and LinkedIn. On X (Twitter) and Threads, this creates a reply to the main post (threading). On YouTube, it posts as a top-level comment. Note: Instagram does not support text-only posts, so this parameter is not applicable for Instagram here. Reddit posting is currently unavailable (503 `reddit_unavailable`). |
| first_comment_media[] | File(s) | No | Image files to attach to the first comment as inline images. Reddit-only, and Reddit posting is currently unavailable (503 `reddit_unavailable`). Not available for scheduled or queued posts. |
| link_url      | String | No       | URL to include as a link preview card. When provided, platforms that support link previews (LinkedIn, Bluesky, Facebook) will display a rich preview card with the page's title, description, and thumbnail image. Platform-specific parameters (`linkedin_link_url`, `bluesky_link_url`, `facebook_link_url`) take priority over this generic parameter. |

Uploads that take more than 59 seconds switch to async even if `async_upload` is `false` — poll [Upload Status](./upload-status) with the `request_id`.

`scheduled_date` returns **202 Accepted** and a `job_id`. Use that id on [Upload Status](./upload-status) while it runs and on [Upload History](./upload-history) after it publishes.

This endpoint supports simultaneous text uploads to X (Twitter), LinkedIn, Facebook, Threads, Reddit, Bluesky, Discord, and Telegram.

### Platform-Specific First Comments

The `first_comment` parameter serves as a fallback. To set a custom first comment for a particular platform, use the optional `[platform]_first_comment` parameter. If provided, it will override the main `first_comment` for that platform.

**Example Optional Parameters:**

*   `facebook_first_comment`: "Let me know your thoughts in the comments!"
*   `x_first_comment`: "Thread incoming! 🧵"
*   `threads_first_comment`: "First comment on Threads!"
*   `reddit_first_comment`: "Source in the comments."
*   `bluesky_first_comment`: "More details in the replies."
*   `linkedin_first_comment`: "Source article in the comments."

### Platform-Specific Titles

The `title` parameter serves as a fallback. To set a custom title for a particular platform, use the optional `[platform]_title` parameter. If provided, it will override the main `title` for that platform.

**Example Optional Parameters:**

*   `linkedin_title`: "A professional insight on the latest industry trends."
*   `x_title`: "New update out now! 📢"
*   `facebook_title`: "Excited to share this with my Facebook friends."
*   `threads_title`: "Just posted something new on Threads!"

### Platform-Specific Parameters

### LinkedIn

| Name                    | Type   | Required | Description                                                                                                | Default     |
|-------------------------|--------|----------|------------------------------------------------------------------------------------------------------------|-------------|
| linkedin_title          | String | No       | Specific text for the LinkedIn post. Fallbacks to `title`.                                                 | `title`     |
| target_linkedin_page_id | String | No       | LinkedIn page ID to upload text to an organization's page. If not provided, posts to the user's personal profile. |             |
| linkedin_link_url       | String | No       | URL to include as a link preview card on the LinkedIn post. LinkedIn will display a rich preview with the page's title, description, and thumbnail. Overrides the generic `link_url` parameter for LinkedIn. | `link_url`  |
| linkedin_visibility | String | No | `PUBLIC`, `CONNECTIONS`, `LOGGED_IN`, `CONTAINER`. Aliases: `visibility`, `linkedinVisibility`. | `PUBLIC` |
| linkedin_disable_reshare | Boolean | No | When `true`, disable reshare. | false |
| linkedin_link_title | String | No | Link-share title (max 400 characters). | - |
| linkedin_link_description | String | No | Link-share description (max 4086 characters). | - |
| linkedin_thumbnail_alt_text | String | No | Alt text for the link thumbnail. | - |
| linkedin_poll_question  | String | No       | Question of a LinkedIn poll. Max 140 characters. Requires `linkedin_poll_options[]`. | - |
| linkedin_poll_options[] | Array  | No       | Poll choices: 2 to 4 options, max 30 characters each. Requires `linkedin_poll_question`. | - |
| linkedin_poll_duration  | String | No       | How long the poll stays open: `ONE_DAY`, `THREE_DAYS`, `SEVEN_DAYS`, `FOURTEEN_DAYS` (the day counts `1`, `3`, `7`, `14` are accepted too). | `SEVEN_DAYS` |

#### LinkedIn Polls

Send `linkedin_poll_question` plus 2-4 `linkedin_poll_options[]` and the post is
published as a poll. The regular post text (`linkedin_title` / `title`) stays as
the commentary above the poll.

```bash
curl --location 'https://api.upload-post.com/api/upload_text' \
  --header 'Authorization: ApiKey YOUR_API_KEY' \
  --form 'user=your_profile' \
  --form 'platform[]=linkedin' \
  --form 'title=We are settling this once and for all.' \
  --form 'linkedin_poll_question=Best day to ship a release?' \
  --form 'linkedin_poll_options[]=Monday' \
  --form 'linkedin_poll_options[]=Wednesday' \
  --form 'linkedin_poll_options[]=Friday' \
  --form 'linkedin_poll_duration=THREE_DAYS'
```

:::warning A poll cannot be edited after it is published
LinkedIn does not allow updating poll content, so the request is validated
before anything is created: a bad question, a wrong option count, an option
over 30 characters or an unknown duration comes back as a `400` and nothing is
posted.

Two more LinkedIn rules apply:

- **Polls and link previews are mutually exclusive.** Sending
  `linkedin_poll_*` together with `linkedin_link_url` / `link_url` is rejected —
  LinkedIn has a single content slot per post.
- **API-created polls are non-sponsored only**, and poll authors may not ask for
  political opinions, health status or other sensitive data (LinkedIn's
  Professional Community Policies).
:::

Polls work on personal profiles and on company pages (`target_linkedin_page_id`),
and they can be scheduled or queued like any other text post.

### X (Twitter)

:::warning URLs are stripped from every X post
Every URL that X would turn into a clickable link is removed from the caption,
title, and `first_comment` before the tweet is created — schemed URLs
(`https://`, `http://`, `ftp://`), `www.` hosts, shorteners (`t.co`, `bit.ly`,
…), bare hostnames with a path (`example.com/foo`), and IPs with a path.

Obfuscated forms (`example[.]com`, `hxxp://`, unicode dots) are **not**
stripped because X does not parse them as links — they display as plain text
and are billed at the normal `$0.015` rate.

**Why:** X charges `$0.200` per "Content: Create (with URL)" vs `$0.015` for
posts without a URL. Stripping happens on every X path (video, photo, text,
scheduled, retried) so usage stays on the cheap tier. See the
[Character Limits page](../resources/character-limits.md#x-twitter-character-limits)
for the full policy. To keep URLs in your X posts, see the
[X Links add-on](../guides/x-links-addon.md).
:::

| Name                        | Type    | Required | Description                                                                                                                           | Default     |
|-----------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------|-------------|
| `x_title`                     | String  | No       | Specific text for the tweet. Fallbacks to `title`. If the text is long, it will be split into a thread.                               | `title`     |
| `x_long_text_as_post`         | Boolean | No       | For X Premium users. When `true`, long text is published as a single post. When `false` (default), it creates a thread if text is long. | `false`     |
| `reply_settings`              | String  | No       | Controls who can reply to the tweet ("following", "mentionedUsers", "subscribers", "verified")                                       | -           |
| `quote_tweet_id`              | String  | No       | ID of the tweet to quote in a quote tweet. Mutually exclusive with `card_uri`, `poll_*`, and `direct_message_deep_link`. **Cannot be combined with media** (see note below). | -           |
| `geo_place_id`                | String  | No       | Place ID for adding geographic location to the tweet                                                                                  | -           |
| `nullcast`                    | Boolean | No       | Whether to publish without broadcasting (promotional/promoted-only posts)                                                             | `false`     |
| `for_super_followers_only`    | Boolean | No       | Tweet exclusive for super followers                                                                                                   | `false`     |
| `community_id`                | String  | No       | Community ID for posting to specific communities                                                                                      | -           |
| `share_with_followers`        | Boolean | No       | Share community post with followers                                                                                                   | `false`     |
| `direct_message_deep_link`    | String  | No       | Link to take the conversation from public timeline to private Direct Message. Mutually exclusive with `card_uri`, `quote_tweet_id`, and `poll_*`. | -           |
| `card_uri`                    | String  | No       | Card URI for Twitter Cards/ads/promoted content. Mutually exclusive with `quote_tweet_id`, `direct_message_deep_link`, and `poll_*`. | -           |
| `poll_options`                | Array   | No       | Array of poll options (2-4 options, max 25 characters each). Mutually exclusive with `card_uri`, `quote_tweet_id`, and `direct_message_deep_link`. | []          |
| `poll_duration`               | Integer | No       | Poll duration in minutes (5-10080, i.e., 5 minutes to 7 days)                                                                         | 1440        |
| `poll_reply_settings`         | String  | No       | Who can reply to poll ("following", "mentionedUsers", "subscribers", "verified"). Requires `poll_options`.                                          | -           |
| `reply_to_id`                 | String  | No       | ID of the tweet to reply to. Creates a reply to the specified tweet.                                                                                | -           |
| `exclude_reply_user_ids`      | Array   | No       | Array of user IDs to exclude from replying to this tweet. Requires `reply_to_id`. | []          |
| `x_paid_partnership`          | Boolean | No       | Paid partnership flag (applied to the first tweet). | `false`     |
| `x_article_title`             | String  | No       | Publishes the post as a long-form **X Article** with this headline. Max 100 characters. Requires X Premium on the connected account. When you send it, the global `title` becomes optional — the headline stands in as the post text. | -           |
| `x_article_body`              | String  | No       | Article body as plain text or light Markdown. Falls back to the post text (`x_title` / `title`) when omitted. Requires `x_article_title`. | post text   |
| `x_article_content_state`     | String  | No       | Raw X `content_state` JSON (`{"blocks": [...], "entities": [...]}`) for callers that build DraftJS themselves. Overrides `x_article_body`. | -           |
| `x_article_draft`             | Boolean | No       | When `true`, the Article is saved as a draft on X instead of being published. | `false`     |
| `x_article_cover_media`       | File, URL, or media_id | No | Cover image for the X Article (multipart ≤ 5 MB, public URL, or numeric `media_id`). | -           |

Note: For Twitter uploads, specify the platform as `"x"` in the `platform[]` array.

#### X Articles (long-form)

Send `x_article_title` and the post is published through X's Articles API
instead of as a tweet or a thread — a full long-form article with headings,
lists and quotes. The body comes from `x_article_body`, or from the normal post
text if you don't send one.

```bash
curl --location 'https://api.upload-post.com/api/upload_text' \
  --header 'Authorization: ApiKey YOUR_API_KEY' \
  --form 'user=your_profile' \
  --form 'platform[]=x' \
  --form 'x_article_title=How we cut upload failures in half' \
  --form 'x_article_body=## The problem

Roughly 8% of uploads failed on the first try.

- Expired tokens
- Oversized videos

> Fixing retries moved the needle more than anything else.

We now retry on the platform errors that are actually transient.'
```

Response (published):

```json
{
  "success": true,
  "results": {
    "x": {
      "success": true,
      "post_id": "1346889436626259968",
      "article_id": "1146654567674912769",
      "draft": false,
      "url": "https://x.com/yourhandle/status/1346889436626259968"
    }
  }
}
```

**Markdown supported in `x_article_body`** — one line per block:

| Markdown          | X block type          |
|-------------------|-----------------------|
| `# Heading`       | `header-one`          |
| `## Heading`      | `header-two`          |
| `### Heading`     | `header-three`        |
| `- ` `* ` `+ `    | `unordered-list-item` |
| `1. ` `2) `       | `ordered-list-item`   |
| `> quote`         | `blockquote`          |
| anything else     | `unstyled` (paragraph)|

Blank lines are separators, not empty paragraphs. There is no code block on X.

**Save as a draft instead of publishing:** add `x_article_draft=true`. The
response carries the `article_id` and `draft: true` with no `post_id` or `url`;
finish and publish it from X when you are ready.

:::warning X Articles need X Premium and are published on their own
- Publishing an Article requires an **X Premium** subscription on the connected
  account. Without it X rejects the request and the error is surfaced as-is.
- `x_article_title` **cannot be combined** with `poll_options[]`,
  `quote_tweet_id`, `reply_to_id`, `card_uri` or `direct_message_deep_link` —
  an Article is published as a standalone post. Sending them together returns a
  `400`.
- Unlike tweets, **URLs are not stripped from `x_article_body`**: the article
  body is not the post text, so it is not affected by the
  [X Links policy](../guides/x-links-addon.md).
:::

`first_comment` (and `x_first_comment`) works with Articles: a published
Article is a post underneath, so the comment is posted as a reply to it. A
draft has nothing to reply to yet, so the comment is skipped.

`quote_tweet_id` and media are mutually exclusive on X. To quote a tweet **and** share an image, post the quote without media, or upload the media as a separate tweet.

`reply_to_id` can return **HTTP 403** on X's Pay-Per-Use tier if the account has not engaged with that author. Reconnecting does not fix it — reply to someone the account already follows or has interacted with.

#### How Twitter Threads Are Created

If your text in the `title` field is longer than 280 characters, our API automatically creates a Twitter thread. You don't need to do anything special. By default, `x_long_text_as_post` is `false`.

**How it works:**

Our system creates natural-looking threads by intelligently splitting your text:

1.  **It groups paragraphs:** The system combines as many paragraphs (text separated by a blank line) as possible into a single tweet without exceeding the character limit.
2.  **It splits long paragraphs:** If a single paragraph is too long for one tweet, it's split into smaller parts. The system first tries to split by line breaks and then by words.

This process ensures your threads are easy to read.

**Example of a thread creation**

If you send this text in the `title`:

```
This is the first paragraph. It is short.

This second paragraph is a bit longer. Our API tries to keep paragraphs together in one tweet.

This is a much longer third paragraph. It probably won't fit with the others. It might even be too long for a single tweet. If so, the API will split it. It will first look for line breaks. If a single line is still too long, it will split it by words. This creates a readable and well-structured Twitter thread automatically.
```

The API will create a thread like this:

**Tweet 1:**
> This is the first paragraph. It is short.
>
> This second paragraph is a bit longer. Our API tries to keep paragraphs together in one tweet.

**Tweet 2:**
> This is a much longer third paragraph. It probably won't fit with the others. It might even be too long for a single tweet. If so, the API will split it. It will first look for line breaks.

**Tweet 3:**
> If a single line is still too long, it will split it by words. This creates a readable and well-structured Twitter thread automatically.

### Facebook

| Name             | Type   | Required | Description                                                       | Default |
|------------------|--------|----------|-------------------------------------------------------------------|---------|
| facebook_title   | String | No       | Specific text for the Facebook post. Fallbacks to `title`.        | `title` |
| facebook_page_id | String | Yes      | Facebook Page ID where the text will be posted.                   | -       |
| facebook_link_url | String | No       | Optional URL to include for link preview in text posts. If provided, it's sent as `link` to the Graph API and Facebook may render a preview card. Some URLs refused as preview cards by Facebook are published with the URL appended to the text and a warning. | -       |
| facebook_call_to_action | JSON | No | Call-to-action object. Requires `facebook_link_url` or `link_url`. | - |
| facebook_child_attachments | JSON array | No | Link carousel: 2–5 objects with `link`. | - |
| facebook_multi_share_end_card | Boolean | No | End card on a link carousel. | - |
| facebook_place_id | String | No | Facebook place ID (`place`). | - |

Connecting Facebook only links the account; it does **not** pick a destination Page. Meta only allows posting to Pages, not personal profiles.

Pass `facebook_page_id` on every upload. If you omit it and exactly one Page is connected, that Page is used. If several are connected, the API returns `available_pages`. Look up ids with [Get Facebook Pages](./get-facebook-pages.md), or [pin a Page](./facebook-page.md) once — a pinned Page takes precedence over `facebook_page_id`.

### Threads

| Name                        | Type    | Required | Description                                                                                                                              | Default |
|-----------------------------|---------|----------|------------------------------------------------------------------------------------------------------------------------------------------|---------|
| `threads_title`             | String  | No       | Specific text for the Threads post. Fallbacks to `title`.                                                                                | `title` |
| `threads_long_text_as_post` | Boolean | No       | If `true`, long text is published as a single post. If `false` (default), a thread is created if the text exceeds 500 characters.        | `false` |
| `threads_topic_tag`         | String  | No       | A topic tag for the post (1-50 characters). Cannot contain periods (.) or ampersands (&). One tag per post. Helps increase reach.        | -       |
| threads_reply_control | String | No | Who can reply: `everyone`, `accounts_you_follow`, `mentioned_only`, `parent_post_author_only`, `followers_only` (alias `followers`). | - |
| threads_reply_to_id | String | No | Numeric post ID to reply to. | - |
| threads_quote_post_id | String | No | Numeric post ID to quote. | - |
| threads_link_attachment | String | No | http(s) URL attached to a text post. Max 5 unique links per post (`THREADS_API__LINK_LIMIT_EXCEEDED`). | - |
| threads_poll_options | String or `threads_poll_options[]` | No | 2–4 options, 1–25 characters each. Text posts only. | - |
| threads_auto_publish_text | Boolean | No | Skip the second publish call on text posts. | false |

Note: To upload content to Threads, specify the platform as `"threads"` in the `platform[]` array.

#### How Threads Are Created

If the text you provide exceeds 500 characters and `threads_long_text_as_post` is `false`, our API will automatically create a thread on Threads, similar to how it works with X (Twitter).

**How it works:**

Our system creates natural-looking threads by intelligently splitting your text:

1.  **It groups paragraphs:** The system combines as many paragraphs as possible into a single post without exceeding the character limit.
2.  **It splits long paragraphs:** If a single paragraph is too long for a post, it is split into smaller parts, first trying to break by line breaks, and if that's not enough, by words.

This process ensures that your Threads are coherent and easy to read, replicating the functionality you already enjoy for X.

### Reddit

:::warning Reddit posting is currently unavailable
Uploads with `platform[]=reddit` return **HTTP 503** with `error_code: "reddit_unavailable"`. Do not include `reddit` in `platform[]` until posting is restored. OAuth connect and comments with `platform=reddit` return the same error.
:::

```json
{
  "success": false,
  "error_code": "reddit_unavailable",
  "error": "Reddit posting is currently unavailable."
}
```

| Name       | Type   | Required | Description                                                        | Default |
|------------|--------|----------|--------------------------------------------------------------------|---------|
| subreddit  | String | Yes      | Destination subreddit, without `r/` (e.g., `python`). Unused while Reddit is unavailable. | -       |
| flair_id   | String | No       | ID of the flair template to apply to the post. Unused while Reddit is unavailable. | -       |
| reddit_link_url | String | No  | URL for a Reddit link post. Unused while Reddit is unavailable. | `link_url` |

### Bluesky

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| bluesky_title | String | No | Specific text for the Bluesky post. Fallbacks to `title`. | `title` |
| bluesky_link_url | String | No | URL to include as a link preview card on the Bluesky post. Bluesky will display a rich external embed with the page's title, description, and thumbnail. Overrides the generic `link_url` parameter for Bluesky. | `link_url` |
| reply_to_id | String | No | URL or AT-URI of the post to reply to. Creates a reply to the specified post. | - |
| bluesky_langs | String | No | Up to 3 BCP-47 codes, comma-separated. | - |
| bluesky_labels | String | No | Comma list of `porn`, `sexual`, `nudity`, `graphic-media`. | - |
| bluesky_threadgate | String | No | Who can reply. Alias: `bluesky_reply_settings`. | everyone |
| bluesky_postgate | String | No | `disable_quotes` (or `true`) to block quotes. Alias: `bluesky_quote_settings`. | quotes allowed |
| bluesky_quote_uri | String | No | Post URL or `at://` URI to quote. Aliases: `bluesky_quote_id`, `bluesky_quote_url`. | - |

Note: To upload content to Bluesky, specify the platform as "bluesky" in the `platform[]` array. The maximum character limit is 300 characters per post.

#### How Bluesky Threads Are Created

If your text exceeds 300 characters, our API automatically creates a Bluesky thread. This works similarly to X (Twitter) and Threads.

**How it works:**

Our system creates natural-looking threads by intelligently splitting your text:

1.  **It groups paragraphs:** The system combines as many paragraphs (text separated by a blank line) as possible into a single post without exceeding 300 characters.
2.  **It splits long paragraphs:** If a single paragraph is too long for one post, it's split into smaller parts. The system first tries to split by line breaks and then by words.

**Example of a thread creation**

If you send this text in the `title`:

```
This is the first paragraph. It is short.

This second paragraph is a bit longer. Our API tries to keep paragraphs together.

This is a much longer third paragraph. It probably won't fit with the others. If so, the API will split it into multiple posts automatically.
```

The API will create a thread like this:

**Post 1:**
> This is the first paragraph. It is short.
>
> This second paragraph is a bit longer. Our API tries to keep paragraphs together.

**Post 2:**
> This is a much longer third paragraph. It probably won't fit with the others. If so, the API will split it into multiple posts automatically.

### Discord

Discord posts go to the channel behind the incoming webhook you connected (see [Connecting Discord](../guides/connecting-accounts.md#connecting-discord-manual-credentials)). A text post is sent as the webhook message `content`.

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| discord_title | String | No | Specific text for the Discord message. Fallbacks to `title`. | `title` |

Note: To upload content to Discord, specify the platform as `"discord"` in the `platform[]` array. The maximum message length is **2000 characters**; longer text is truncated. Discord does not support analytics.

### Telegram

Telegram posts are delivered by your connected bot to the chat/channel you set up (see [Connecting Telegram](../guides/connecting-accounts.md#connecting-telegram-manual-credentials)). A text post is sent via `sendMessage`.

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| telegram_title | String | No | Specific text for the Telegram message. Fallbacks to `title`. | `title` |

Note: To upload content to Telegram, specify the platform as `"telegram"` in the `platform[]` array. The maximum message length is **4096 characters**; longer text is truncated. Telegram does not support analytics.

### Slack

Slack posts are delivered to the channel behind the Incoming Webhook you connected (see [Connecting Slack](../guides/connecting-accounts.md#connecting-slack-manual-credentials)). The text is sent as the webhook message.

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| slack_title | String | No | Specific text for the Slack message. Fallbacks to `title`. | `title` |

Note: To post to Slack, specify `"slack"` in the `platform[]` array. Slack is text-only (Incoming Webhooks cannot upload media). Max **40000** characters. Slack does not support analytics.

### Mastodon

Mastodon publishes a status ("toot") to your connected instance (see [Connecting Mastodon](../guides/connecting-accounts.md#connecting-mastodon-manual-credentials)).

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| mastodon_title | String | No | Specific text for the Mastodon status. Fallbacks to `title`. | `title` |

Note: To post to Mastodon, specify `"mastodon"` in the `platform[]` array. The default limit is **500** characters (configurable per instance). Mastodon also supports photos and video. Mastodon does not support analytics.

### Nostr

Nostr signs a note with your key and broadcasts it to your relays (see [Connecting Nostr](../guides/connecting-accounts.md#connecting-nostr-manual-credentials)).

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| nostr_title | String | No | Specific text for the Nostr note. Fallbacks to `title`. | `title` |

Note: To post to Nostr, specify `"nostr"` in the `platform[]` array. Nostr is text-only (media is referenced by URL, not uploaded). Max **32768** characters. Nostr does not support analytics.

### Lemmy

Lemmy creates a post in the community you connected (see [Connecting Lemmy](../guides/connecting-accounts.md#connecting-lemmy-manual-credentials)). Lemmy posts always require a title.

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| lemmy_title | String | No | Title of the Lemmy post. If omitted, it is derived from the first line of the content. Fallbacks to `title`. | `title` |

Note: To post to Lemmy, specify `"lemmy"` in the `platform[]` array. Title max **200** characters, body max **10000**. Lemmy also supports photo posts. Lemmy does not support analytics.

### Dev.to

Dev.to publishes a Markdown article (see [Connecting Dev.to](../guides/connecting-accounts.md#connecting-devto-manual-credentials)). The text content becomes the article body.

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| devto_title | String | No | Article title. If omitted, it is derived from the first line of the content. Fallbacks to `title`. | `title` |

Note: To post to Dev.to, specify `"devto"` in the `platform[]` array. Title max **250** characters, body (Markdown) max **250000**. Dev.to does not support analytics.

### Hashnode

Hashnode publishes a Markdown article to your publication (see [Connecting Hashnode](../guides/connecting-accounts.md#connecting-hashnode-manual-credentials)).

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| hashnode_title | String | No | Article title. If omitted, it is derived from the first line of the content. Fallbacks to `title`. | `title` |

Note: To post to Hashnode, specify `"hashnode"` in the `platform[]` array. Title max **250** characters, body (Markdown) max **100000**. Hashnode does not support analytics.

### WordPress

WordPress publishes a post to your site (see [Connecting WordPress](../guides/connecting-accounts.md#connecting-wordpress-manual-credentials)). The text content becomes the post body.

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| wordpress_title | String | No | Post title. If omitted, it is derived from the first line of the content. Fallbacks to `title`. | `title` |

Note: To post to WordPress, specify `"wordpress"` in the `platform[]` array. Title max **200** characters, body max **500000**. WordPress also supports photos and video. WordPress does not support analytics.

### Whop

Whop creates a post in the community forum ("experience") you connected (see [Connecting Whop](../guides/connecting-accounts.md#connecting-whop-manual-credentials)).

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| whop_title | String | No | Title of the Whop forum post. Fallbacks to `title`. | `title` |

Note: To post to Whop, specify `"whop"` in the `platform[]` array. Max **20000** characters. Posting requires an API key with the `forum:post:create` permission. Whop does not support analytics.

### Listmonk

Listmonk creates and sends an email campaign to the list you connected (see [Connecting Listmonk](../guides/connecting-accounts.md#connecting-listmonk-manual-credentials)).

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| listmonk_title | String | No | Email subject line. If omitted, it is derived from the first line of the content. Fallbacks to `title`. | `title` |

Note: To post to Listmonk, specify `"listmonk"` in the `platform[]` array. The text content becomes the email body. Subject max **500** characters, body max **200000**. Listmonk does not support analytics.

### Google Business Profile

| Name | Type | Required | Description | Default |
|------|------|----------|-------------|---------|
| gbp_location_id | String | No* | The location to post to. Use [Get Google Business Locations](./get-google-business-locations.md) to list available locations. | Auto |
| gbp_topic_type | String | No | Post type: `STANDARD` (default), `EVENT`, or `OFFER`. | `STANDARD` |
| gbp_cta_type | String | No | Call-to-action button: `BOOK`, `ORDER`, `SHOP`, `LEARN_MORE`, `SIGN_UP`, `CALL`. Alias: `cta_type`. Other spellings (`cta_action_type`, `gbp_cta_action_type`) are accepted too, but the canonical name is `gbp_cta_type`. An unknown value is rejected with `400` instead of publishing the post without its button. | - |
| gbp_cta_url | String | Conditional | URL the button opens. Required for every type except `CALL`, which dials the location's phone number and must not carry a URL. Missing URL → `400`. Alias: `cta_url`. | - |
| gbp_post_type | String | No | Set to `MEDIA` (also accepts `PHOTO` or `GALLERY`) to publish an attached photo to the location's **Media/Gallery** tab instead of creating a Local Post. See [Publishing to the Media/Gallery tab](./upload-photo.md#publishing-to-the-mediagallery-tab). | - |
| gbp_upload_to_gallery | Boolean | No | Alternative to `gbp_post_type`. Set to `true` for the same Media/Gallery behaviour. | `false` |
| gbp_media_category | String | No | Category assigned to the uploaded photo in the Media tab. Alias: `media_category`. Allowed: `COVER`, `PROFILE`, `LOGO`, `EXTERIOR`, `INTERIOR`, `PRODUCT`, `AT_WORK`, `FOOD_AND_DRINK`, `MENU`, `COMMON_AREA`, `ROOMS`, `TEAMS`, `ADDITIONAL`. | `ADDITIONAL` |

If `gbp_location_id` is omitted and the account has exactly one location, that location is used. If several are connected, the API asks you to pick one.

The Media/Gallery flow publishes a **photo**, so it is normally used from [`/api/upload_photos`](./upload-photo.md#publishing-to-the-mediagallery-tab). The fields are accepted here too, but a text-only request that selects Media/Gallery with no media returns `400` (`error_code: MEDIA_REQUIRED`). Omitting `gbp_post_type` keeps Local Post behaviour.

**Event parameters** (when `gbp_topic_type` is `EVENT`):

| Name | Type | Required | Description |
|------|------|----------|-------------|
| gbp_event_title | String | Yes | Title of the event. |
| gbp_event_start_date | String | Yes | Start date in `YYYY-MM-DD` format. |
| gbp_event_start_time | String | No | Start time in `HH:MM` format (24h). |
| gbp_event_end_date | String | Yes | End date in `YYYY-MM-DD` format. |
| gbp_event_end_time | String | No | End time in `HH:MM` format (24h). |

**Offer parameters** (when `gbp_topic_type` is `OFFER`):

| Name | Type | Required | Description |
|------|------|----------|-------------|
| gbp_coupon_code | String | No | Coupon or promo code. Alias of `gbp_offer_coupon` (old name wins if both are sent). |
| gbp_redeem_url | String | No | URL where the offer can be redeemed. Alias of `gbp_offer_redeem_url`. |
| gbp_terms | String | No | Terms and conditions of the offer. Alias of `gbp_offer_terms`. |
| gbp_language_code | String | No | BCP-47 language. Default `"en"`. |

### Example Requests

### Upload Text to X (Twitter)

<Tabs groupId="lang">
<TabItem value="curl" label="cURL">

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=x' \
  -F 'title="This is my tweet content!"' \
  -X POST https://api.upload-post.com/api/upload_text
```

</TabItem>
<TabItem value="python" label="Python">

```python
import requests

response = requests.post(
    "https://api.upload-post.com/api/upload_text",
    headers={"Authorization": "Apikey your-api-key-here"},
    data={
        "user": "test",
        "platform[]": "x",
        "title": "This is my tweet content!",
    },
)
print(response.json())
```

</TabItem>
<TabItem value="js" label="JavaScript">

```javascript
const form = new FormData();
form.append("user", "test");
form.append("platform[]", "x");
form.append("title", "This is my tweet content!");

const response = await fetch("https://api.upload-post.com/api/upload_text", {
  method: "POST",
  headers: { Authorization: "Apikey your-api-key-here" },
  body: form,
});
console.log(await response.json());
```

</TabItem>
</Tabs>

### Create a Twitter Thread

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=x' \
  -F 'title="This is the first paragraph of a thread.\n\nThis is the second paragraph. Because this whole text is longer than 280 characters, the API will automatically create a thread. You can also add more paragraphs to create longer and more detailed threads easily."' \
  -X POST https://api.upload-post.com/api/upload_text
```

### Upload Text to LinkedIn with Link Preview

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=linkedin' \
  -F 'title="Check out this great article about renewable energy!"' \
  -F 'linkedin_link_url="https://example.com/article"' \
  -X POST https://api.upload-post.com/api/upload_text
```

### Upload Text to LinkedIn (Personal Profile)

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=linkedin' \
  -F 'title="Exciting news to share on LinkedIn!"' \
  -X POST https://api.upload-post.com/api/upload_text
```

### Upload Text to LinkedIn (Organization Page)

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=linkedin' \
  -F 'title="Our company is launching a new product!"' \
  -F 'target_linkedin_page_id="your_linkedin_page_id_here"' \
  -X POST https://api.upload-post.com/api/upload_text
```

### Upload Text to Facebook Page

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'title="This is a test post to Facebook using the title field for content!"' \
  -F 'user="test2"' \
  -F 'platform[]=facebook' \
  -F 'facebook_page_id="your_facebook_page_id_here"' \
  -F 'facebook_link_url="https://example.com/article"' \
  -X POST https://api.upload-post.com/api/upload_text
```

### Upload Text to Threads and Twitter (X)

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'title="This is a cross-post to Threads and X!"' \
  -F 'user="test"' \
  -F 'platform[]=threads' \
  -F 'platform[]=x' \
  -X POST https://api.upload-post.com/api/upload_text
  ```

### Upload Text to Reddit

Reddit posting is currently unavailable. A request with `platform[]=reddit` returns **HTTP 503**:

```json
{
  "success": false,
  "error_code": "reddit_unavailable",
  "error": "Reddit posting is currently unavailable."
}
```

### Upload Text to Bluesky

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=bluesky' \
  -F 'title="This is my Bluesky post!"' \
  -X POST https://api.upload-post.com/api/upload_text
```

### Upload Text to Bluesky with Link Preview

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=bluesky' \
  -F 'title="Great read on climate policy!"' \
  -F 'bluesky_link_url="https://example.com/article"' \
  -X POST https://api.upload-post.com/api/upload_text
```

### Create a Bluesky Thread

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=bluesky' \
  -F 'title="This is the first paragraph of a thread.\n\nThis is the second paragraph. Because this whole text is longer than 300 characters, the API will automatically create a thread. You can add more paragraphs to create longer threads easily."' \
  -X POST https://api.upload-post.com/api/upload_text
```

### Upload Text to Discord

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=discord' \
  -F 'title="Hello from Upload-Post!"' \
  -X POST https://api.upload-post.com/api/upload_text
```

### Upload Text to Telegram

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=telegram' \
  -F 'title="Hello from Upload-Post!"' \
  -X POST https://api.upload-post.com/api/upload_text
```

### Upload Text to Multiple Platforms with Link Preview

Use the generic `link_url` parameter to add a link preview card across all supported platforms at once:

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=linkedin' \
  -F 'platform[]=bluesky' \
  -F 'platform[]=facebook' \
  -F 'facebook_page_id="your_facebook_page_id_here"' \
  -F 'title="Check out our latest article on renewable energy!"' \
  -F 'link_url="https://example.com/article"' \
  -X POST https://api.upload-post.com/api/upload_text
```

### Reply to a Tweet on X (Twitter)

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=x' \
  -F 'title="This is my reply to a tweet!"' \
  -F 'reply_to_id="1346889436626259968"' \
  -X POST https://api.upload-post.com/api/upload_text
```

### Reply to a Tweet on X (Twitter) with Excluded Users

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=x' \
  -F 'title="This is my reply to a tweet with some users excluded!"' \
  -F 'reply_to_id="1346889436626259968"' \
  -F 'exclude_reply_user_ids[]=1234567890' \
  -F 'exclude_reply_user_ids[]=0987654321' \
  -X POST https://api.upload-post.com/api/upload_text
```

### Responses

- 200 OK (synchronous, finished fast)

```json
{
  "success": true,
  "results": {
    "x":        { "success": true, "url": "https://x.com/..." },
    "facebook": { "success": false, "error": "Facebook Page ID is required for text posts to Facebook." }
  },
  "usage": { "count": 14, "limit": 100, "last_reset": "..." }
}
```

- 200 OK (asynchronous/background started or sync→background fallback)

```json
{
  "success": true,
  "message": "Text post initiated successfully in background.",
  "request_id": "1a2b3c4d5e...",
  "total_platforms": 1
}
```

- 202 Accepted (scheduled)

```json
{
  "success": true,
  "job_id": "scheduler_job_789",
  "scheduled_date": "2025-09-22T10:00:00Z"
}
```

- 400 Bad Request
  - Missing `title` (content), `user`, `platform[]`, invalid platforms, invalid `scheduled_date`. For Facebook without `facebook_page_id`, the per-platform result will include an error entry for `facebook`. Reddit-only requests return **503** `reddit_unavailable`.

- 401 Unauthorized: `{ "success": false, "message": "Invalid or expired token" }`

- 403 Forbidden (plan restrictions)

- 404 Not Found (e.g., user not found)

- 429 Too Many Requests (monthly limit exceeded; includes current usage)

```json
{
  "success": false,
  "message": "This upload would exceed your monthly limit.",
  "usage": { "count": 10, "limit": 10, "last_reset": "..." }
}
```

- 500 Internal Server Error: `{ "success": false, "error": "Detailed error message" }`

Notes
- When async or when sync falls back to background, use `GET /api/uploadposts/status?request_id={request_id}` to poll progress.
- Per-platform results are returned under `results.{platform}` and may include fields like `url`, platform-specific IDs, or `error`. A result with `skipped: true` means the profile has no account for that platform (see [Unconnected platforms](#unconnected-platforms)).

### Unconnected platforms

Each profile only has accounts for the platforms you connected to it. When a request lists several platforms and the profile has **no account for some of them**, the upload is **not rejected**: the connected platforms are published normally and each unconnected one comes back in `results` as *skipped* (nothing is posted there and it is not counted as a failure in your dashboard):

```json
{
  "success": true,
  "results": {
    "instagram": { "success": true, "url": "https://instagram.com/p/..." },
    "linkedin": {
      "success": false,
      "skipped": true,
      "skip_reason": "profile_platform_not_configured",
      "error": "Profile creator has no Linkedin account configured",
      "error_code": "profile_platform_mapping_invalid",
      "failure_stage": "profile_platform_validation"
    }
  }
}
```

For asynchronous uploads the same per-platform entry is returned by [`GET /api/uploadposts/status`](./upload-status.md), and skipped platforms count as finished, so `completed` reaches `total` as soon as the connected platforms are done.

If **none** of the requested platforms is connected to the profile, the request is rejected with `400`:

```json
{
  "success": false,
  "message": "None of the requested platforms are valid for profile \"creator\". Profile creator has no Linkedin account configured",
  "invalid_platforms": { "linkedin": "Profile creator has no Linkedin account configured" }
}
```

**Scheduled posts** (`scheduled_date`) keep **every** requested platform, connected or not, and are validated again when the job runs. Connect the missing account before the scheduled time and the post will be published there too; otherwise that platform is reported as skipped at execution time.


---
# Upload Video
URL: https://docs.upload-post.com/api/upload-video

# Upload Video

Upload video to various social media platforms using this endpoint.

### Endpoint

```http
POST /api/upload
```

### Headers

| Name | Value | Description |
|------|-------|-------------|
| Authorization | Apikey your-api-key-here | Your API key for authentication |
| Idempotency-Key | unique-string | Optional. Prevents duplicate uploads if the same request is retried (e.g., after a timeout). Can also be sent as `X-Idempotency-Key` or `X-Request-Id`. When provided, if a matching upload job already exists, the API returns the existing job instead of creating a duplicate. |

### Common Parameters

| Name | Type | Required | Description |
|------|------|----------|-------------|
| user | String | Yes | User identifier |
| platform[] | Array | Yes | Platform(s) to upload to (e.g., "tiktok", "instagram", "linkedin", "youtube", "facebook", "twitter", "threads", "pinterest", "bluesky", "reddit", "discord", "telegram", "google_business", "mastodon", "wordpress") |
| video | File | Yes | The video file to upload (can be a file upload or a video URL) |
| title | String | Conditional | Default title of the video. **Required** for YouTube and Reddit. Optional for all other platforms (TikTok, Instagram, Facebook, LinkedIn, X, Threads, Bluesky, Pinterest). |
| description | String | No | Optional extended text used only on LinkedIn commentary, Facebook descriptions, YouTube descriptions, and Pinterest notes. Ignored elsewhere. |
| scheduled_date | String (ISO-8601) | No | Optional date/time (ISO-8601) to schedule publishing, e.g., "2024-12-31T23:45:00Z". Must be in the future (≤ 365 days). Omit for immediate upload. |
| timezone | String (IANA) | No | Optional timezone identifier (e.g., "Europe/Madrid", "America/New_York"). If provided, `scheduled_date` is interpreted in this timezone. Defaults to UTC if omitted. See [IANA Time Zone Database](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones) for valid values. |
| external_id | String | No | Your own identifier for this post (max 255 chars), echoed back by [Scheduled Posts](./schedule-posts), [Upload Status](./upload-status) and [Upload History](./upload-history). Use it to map a post back to a record in your own system instead of matching on `title`, which is editable. Can also be sent as an `X-External-Id` header. It is a label only — reusing one never blocks a publish; for that, send an `Idempotency-Key` header. |
| request_id | String | No | Client-provided request identifier. If omitted, the server generates one. Returned in every response and used to track the upload via [Upload Status](./upload-status). Useful when `async_upload=true` and the HTTP response might be lost (e.g., timeout). Can also be sent as an `X-Request-Id` header. |
| async_upload | Boolean | No | If `true`, the request returns immediately with a `request_id` and processes in the background. When `video` is a URL, we fetch the file in the background too, so the `request_id` comes back within seconds regardless of file size (Google Drive links are the exception: they are fetched before the response). When `video` is an uploaded file, the response is sent once the upload body has been received. See [Upload Status](./upload-status). |
| add_to_queue | Boolean | No | If `true`, automatically schedules the post to your next available queue slot. Cannot be used with `scheduled_date`. See [Queue System](./queue-system). |
| max_posts_per_slot | Integer | No | Override the profile's max posts per slot setting for this request. Only used when `add_to_queue=true`. See [Queue System](./queue-system). |
| first_comment | String | No | Automatically post a first comment after publishing. Supported on Instagram, Facebook, Threads, Bluesky, X, YouTube, LinkedIn, and TikTok. On X (Twitter) and Threads, this creates a reply to the main post. For X threads, the comment is posted as a reply to the last tweet in the thread. On YouTube, it posts as a top-level comment on the video. On TikTok it needs the `comments` capability and never fails the publish — see the note below. Reddit posting is currently unavailable (503 `reddit_unavailable`). |
| first_comment_media[] | File(s) | No | Image files to attach to the first comment as inline images. Reddit-only, and Reddit posting is currently unavailable (503 `reddit_unavailable`). Not available for scheduled or queued posts. |

Uploads that take more than 59 seconds switch to async even if `async_upload` is `false` — poll [Upload Status](./upload-status) with the `request_id`. If the HTTP client times out (for example a 504), **do not resend**; send your own `request_id` up front. `Idempotency-Key` stops duplicates.

`scheduled_date` returns **202 Accepted** and a `job_id`. Use that id on [Upload Status](./upload-status) while it runs and on [Upload History](./upload-history) after it publishes.

### Platform-Specific First Comments

The `first_comment` parameter serves as a fallback. To set a custom first comment for a particular platform, use the optional `[platform]_first_comment` parameter. If provided, it will override the main `first_comment` for that platform.

**Example Optional Parameters:**

*   `instagram_first_comment`: "Follow for more content! #photography"
*   `facebook_first_comment`: "Let me know your thoughts in the comments!"
*   `x_first_comment`: "Thread incoming! 🧵"
*   `threads_first_comment`: "First comment on Threads!"
*   `youtube_first_comment`: "Subscribe for more videos!"
*   `reddit_first_comment`: "Source in the comments."
*   `bluesky_first_comment`: "More details in the replies."
*   `linkedin_first_comment`: "Source article in the comments."
*   `tiktok_first_comment`: "Full tutorial in the link in bio."

:::info TikTok first comments need a reconnected account
`first_comment` / `tiktok_first_comment` work on TikTok, on video and photo
posts alike, but only on a connection that reports the `comments`
[capability](./user-profiles.md#account-capabilities) — TikTok grants comment
permission at the moment the account is connected, so the account owner has to
have reconnected it from
[Manage Users](https://app.upload-post.com/manage-users).

**The first comment never fails the publish.** By the time it is attempted the
post is already live, so anything that goes wrong comes back as a plain-string
entry in the response's `warnings` array while `success` stays `true`. A failure
returned as an error would make your retry logic republish the post and you
would end up with it twice.

```json
{
    "success": true,
    "post_id": "7401234567890123456",
    "warnings": [
        "A first comment on TikTok needs a reconnected TikTok account. The post was published without it. Reconnect your TikTok account from Manage Users (https://app.upload-post.com/manage-users)."
    ]
}
```

When it does go through, the response carries `"first_comment_posted": true`.

It is also skipped — with a warning, never silently — when there is nothing to
comment on: a post sent to **drafts** (`post_mode=MEDIA_UPLOAD`, or the alias
`tiktok_upload_to_draft=true`) is not published yet, and occasionally TikTok has not
returned the post id when the publish confirms.
:::

### Platform-Specific Titles

The `title` parameter serves as a fallback. To set a custom title for a particular platform, use the optional `[platform]_title` parameter. If provided, it will override the main `title` for that platform.

**Example Optional Parameters:**

* `instagram_title`: "Check out my latest reel on Instagram! #reels"
* `facebook_title`: "Excited to share this new video with my Facebook friends and family."
* `tiktok_title`: "New TikTok video just dropped! 🔥"
* `linkedin_title`: "A professional insight on the latest industry trends, discussed in this video."
* `x_title`: "New video out now! 📢"
* `youtube_title`: "My new YouTube video is live!"
* `pinterest_title`: "An inspiring video pin."
* `reddit_title`: "Check out this video!"

### Platform-Specific Parameters

### TikTok

For more information about Tiktok API parameters, visit the [Tiktok API documentation](https://developers.tiktok.com/doc/content-posting-api-reference-direct-post?enter_method=left_navigation).

| Name | Type | Required | Description | Default |
|------|------|----------|-------------|---------|
| tiktok_title | String | No | Specific title for the TikTok post (max 90 characters for photo posts, 2200 for video). Fallbacks to `title`. | `title` |
| privacy_level | String | No | `PUBLIC_TO_EVERYONE`, `MUTUAL_FOLLOW_FRIENDS`, `FOLLOWER_OF_CREATOR`, `SELF_ONLY`. **TikTok decides per account which of these are available** — a private account has no `PUBLIC_TO_EVERYONE`. Asking for one the account does not have is rejected with `tiktok_privacy_unavailable`, and the error lists the ones it does have. Omit the field to keep the account's own default. | account default (public for a public account) |
| disable_duet | Boolean | No | Disable duet feature | false |
| disable_comment | Boolean | No | Disable comments | false |
| disable_stitch | Boolean | No | Disable stitch feature | false |
| post_mode | String | No | `DIRECT_POST` publishes immediately. `MEDIA_UPLOAD` sends the media to TikTok drafts (same public idea as `tiktok_upload_to_draft=true`). Either field works on any TikTok account; do not pick a different one for Business vs standard. | `DIRECT_POST` |
| disable_inbox_fallback | Boolean | No | When `true`, a `DIRECT_POST` upload that hits TikTok's daily active-user cap returns the `reached_active_user_cap` error instead of being delivered to the inbox as a draft. Use this if your integration has its own retry/reschedule logic — inbox drafts cannot be deleted via TikTok's API. See the [Active User Cap guide](/guides/reached-active-user-cap-error). Only applies to TikTok connections that still publish through the older route (their `capabilities` list includes `inbox_fallback`); a reconnected account is not subject to the cap, so the field has nothing to do and is ignored. | `false` |
| cover_timestamp | Integer | No | Timestamp in milliseconds for video cover | 1000 |
| tiktok_cover_image_url | String (URL) | No | Public URL of a custom cover image. Takes precedence over `cover_timestamp`. Formats: JPG/JPEG/WebP/PNG, max 20 MB. Needs the `cover_image` capability. | - |
| tiktok_cover_image | File | No | Binary alternative to `tiktok_cover_image_url`. If both are sent, `tiktok_cover_image_url` wins. Needs the `cover_image` capability. | - |
| tiktok_music_id | String | No | Track from TikTok's Commercial Music Library to attach to the video. Get it from [Get TikTok Trending Music](./get-tiktok-music.md). Needs the `music` capability. | - |
| tiktok_music_volume | Integer | No | Volume of the attached track, `0`–`100`. Needs the `music` capability. | 50 (when `tiktok_music_id` is set) |
| tiktok_music_start | Integer | No | Offset **in milliseconds** inside the track where playback starts. Needs the `music` capability. | - |
| tiktok_music_end | Integer | No | Offset **in milliseconds** inside the track where playback ends. Needs the `music` capability. | - |
| tiktok_original_sound_volume | Integer | No | Volume of the audio embedded in your own video, `0`–`100`. Set it explicitly to control the mix when you add a track. Needs the `music` capability. | 50 (when music is present) |
| tiktok_location_id | String | No | TikTok place id to tag on the post. Get it from [Get TikTok Locations](./get-tiktok-locations.md). Requires `tiktok_location_name`. Needs the `location` capability. | - |
| tiktok_location_name | String | Conditional | Display name of the place. **Required** whenever `tiktok_location_id` is sent. Needs the `location` capability. | - |
| tiktok_upload_to_draft | Boolean | No | Alias of `post_mode=MEDIA_UPLOAD`. `true` is the same draft mode — prefer `post_mode`. Also accepted as `upload_to_draft`. | false |
| brand_content_toggle | Boolean| No       | Set to `true` for paid partnerships that promote third-party brands.        | false   |
| brand_organic_toggle | Boolean| No       | Set to `true` when promoting the creator's own business.                    | false   |
| is_aigc | Boolean | No | Set to `true` to label the video as AI-generated content ("Creator labeled as AI-generated" tag on TikTok). Also accepted via the cross-platform `is_ai_generated` alias. Direct Post only. | false |
| tiktok_is_ai_generated | Boolean | No | TikTok-specific alias of `is_aigc`; both are accepted and mean the same thing. | false |
| tiktok_is_ads_only | Boolean | No | When `true`, the video is ads-only (`post_info.is_ads_only`). Alias: `is_ads_only`. Omitted by default. | - |
| tiktok_tto_invite_link | String | No | TikTok One invite link. Alias: `tto_invite_link`. Requires branded content (`brand_content_toggle=true`, or `disclose_commercial` + branded content); otherwise `error_code=invalid_parameter`. | - |

Draft mode (`post_mode=MEDIA_UPLOAD`, alias `tiktok_upload_to_draft=true`) sends the video to TikTok drafts to finish in the app. Same field on Business and standard accounts.

The `title` is **not** attached to a video draft: TikTok's inbox accepts only the file. Keep the caption and paste it in the app; it is delivered automatically only with `DIRECT_POST`.

When TikTok's daily active-user cap is hit, a `DIRECT_POST` is retried as `MEDIA_UPLOAD` instead of failing. The response still has `success: true`, but the video is in the inbox (`"fallback_to_inbox": true`, id starts with `v_inbox_file`). Business-plan accounts get `error_code: "reached_active_user_cap"` instead. Opt out per upload with `disable_inbox_fallback=true`. See the [Active User Cap guide](/guides/reached-active-user-cap-error).

The global `description` field is ignored for TikTok uploads.

#### TikTok specifics

:::info Check the connection's capabilities before sending optional fields
The optional `tiktok_*` fields above are available on connections that declare
the matching capability. Call
[`GET /api/uploadposts/users`](./user-profiles.md#account-capabilities) and look
at the `capabilities` list on the profile's TikTok account (`music`, `location`,
`cover_image`, `cover_timestamp`, `draft`, `photo_privacy`, `video_privacy`,
`inbox_fallback`, `profile_analytics`).

If your connection does not declare a capability, the post is **not rejected**:
it publishes normally and the response includes a `warnings` entry naming the
field that was ignored. Reconnect the TikTok account from
[Manage Users](https://app.upload-post.com/manage-users) to enable it — your API
calls stay exactly the same.
:::

- **`privacy_level` is per account, not per API.** Videos accept the same four
  privacy levels photo posts do, but the set an account may actually use is
  decided by TikTok: a **private** account, for instance, is offered
  `FOLLOWER_OF_CREATOR`, `MUTUAL_FOLLOW_FRIENDS` and `SELF_ONLY` and has no
  `PUBLIC_TO_EVERYONE` at all. Asking for a level the account does not have
  fails fast with `error_code: "tiktok_privacy_unavailable"` and an error
  message listing the levels it does have — nothing is sent to TikTok. Omit
  `privacy_level` and TikTok applies the account's own default. To offer only
  the levels that will work, ask
  [`GET /api/uploadposts/tiktok/settings`](./get-tiktok-settings.md) and read
  `privacy_level_options`. Photo posts take the same field — see
  [Upload Photo](./upload-photo.md#tiktok).
- **Don't silence your own audio.** When you attach a `tiktok_music_id`, TikTok
  mixes that track **over** your video's audio. Set `tiktok_original_sound_volume`
  (0–100) to control the mix; Upload-Post defaults it to `50` when music is
  present so your original audio is not muted.
- **Publishing is asynchronous on TikTok's side.** TikTok downloads the media
  first and publishes afterwards, so poll [Upload Status](./upload-status.md)
  instead of assuming the post is live the moment the API returns. The post URL
  and post id can take up to ~3 minutes to appear after TikTok reports the
  publish as complete.
- **Media requirements:** MP4/MOV/WebM, ≤ 4 GB, 3–600 s, ≥ 360 px on the shortest
  side, 23–60 fps. Caption up to 2,200 characters and 30 mentions. Custom cover
  images: JPG/JPEG/WebP/PNG, max 20 MB.
- **Rate limits:** TikTok allows **6 posts per minute** and **15 posts per day**
  per TikTok account.

**Example — video with a music track, a location tag and a custom cover:**

```bash
curl -X POST https://api.upload-post.com/api/upload \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user=your_profile' \
  -F 'platform[]=tiktok' \
  -F 'title=A morning at the museum 🖼️' \
  -F 'video=@/path/to/video.mp4' \
  -F 'tiktok_music_id=7012345678901234567' \
  -F 'tiktok_music_volume=70' \
  -F 'tiktok_music_start=15000' \
  -F 'tiktok_music_end=45000' \
  -F 'tiktok_original_sound_volume=30' \
  -F 'tiktok_location_id=v2_2f4a1b9c8e' \
  -F 'tiktok_location_name=Museo Nacional del Prado' \
  -F 'tiktok_cover_image_url=https://example.com/covers/museum.jpg' \
  -F 'tiktok_is_ai_generated=false'
```

### Instagram

For more information about Instagram API parameters, visit the [Instagram Graph API documentation](https://developers.facebook.com/docs/instagram-platform/instagram-graph-api/reference/ig-user/media/).

| Name | Type | Required | Description | Default |
|------|------|----------|-------------|---------|
| instagram_title | String | No | Specific title for the Instagram post. Fallbacks to `title`. | `title` |
| media_type | String | No | Type of media ("REELS" or "STORIES") | "REELS" |
| share_mode | String | No | Reel posting mode. See Trial Reels below for details. | "CUSTOM" |
| share_to_feed | Boolean | No | Whether to share to feed (only for regular Reels, not Trial Reels) | true |
| collaborators | String | No | Comma-separated list of collaborator usernames (not available for Trial Reels) | - |
| cover_url | String | No | URL for custom video cover. You can also send a binary image via the `cover_image` field (see below). | - |
| cover_image | File | No | Binary cover image file (JPEG, ≤ 8MB). Uploaded to a public URL automatically. If both `cover_image` and `cover_url` are provided, `cover_url` takes precedence. | - |
| audio_name | String | No | Name of the audio track embedded in your video | - |
| user_tags | String | No | Users to tag on the Reel. Accepts a comma-separated list (e.g., `"@user1, user2"`) — Instagram does **not** require coordinates for video tags. | - |
| location_id | String | No | Instagram location ID | - |
| thumb_offset | String | No | Timestamp offset for video thumbnail, expressed in milliseconds | - |
| is_ai_generated | Boolean | No | Set to `true` to self-disclose the video as AI-generated. Instagram shows an "AI info" label under the account name. The label cannot be added or removed after publishing. | false |

> **Tagging on photo posts is different.** For `/upload_photos`, Instagram requires `x`/`y` coordinates in a JSON array and silently drops username-only tags. See [Upload Photo → Instagram `user_tags`](./upload-photo.md#note-on-instagram-user_tags-for-photo-posts).

#### Trial Reels (share_mode)

Trial Reels allow you to test content with non-followers first to see how it performs before sharing with your followers. This feature is available for public Instagram accounts with at least 1,000 followers.

**Available `share_mode` values:**

| Value | Description |
|-------|-------------|
| `CUSTOM` | Regular Reel (default) - Shown to all followers immediately |
| `TRIAL_REELS_SHARE_TO_FOLLOWERS_IF_LIKED` | Trial Reel with auto-share - Shown to non-followers first. If it performs well within 72 hours, Instagram automatically shares it with your followers |
| `TRIAL_REELS_DONT_SHARE_TO_FOLLOWERS` | Trial Reel without auto-share - Shown only to non-followers. You decide later in the Instagram app if you want to share with followers |

**Important notes about Trial Reels:**
- Only you can see that a Reel is marked as a Trial. To everyone else, it appears as a regular Reel.
- Your followers won't see the Trial on your profile or in their feeds unless you (or Instagram, if auto-share is enabled) choose to share it.
- Collaborators cannot be added to Trial Reels.
- There may be limits on how many Trial Reels you can publish within a certain period.

#### Note on Instagram `audio_name`

- **Scope**: Reels only, and only for the original audio embedded in your uploaded video. It does not let you pick licensed/trending music from Instagram’s library via API.
- **Limit**: You can rename only once (when creating the Reel via API, or later from the audio page if you are the audio owner).
- **Behavior**: The Reel is published using the audio embedded in your video and displays the name you provide in `audio_name`.

The global `description` field is ignored for Instagram video uploads.

### LinkedIn

For more information about LinkedIn API parameters, visit the [LinkedIn Marketing API documentation](https://learn.microsoft.com/en-us/linkedin/marketing/community-management/shares/posts-api?view=li-lms-2025-02&tabs=http).

| Name                    | Type   | Required | Description                                          | Default     |
|-------------------------|--------|----------|------------------------------------------------------|-------------|
| linkedin_title          | String | No       | Specific title for the LinkedIn post. Fallbacks to `title`. | `title` |
| linkedin_description or description    | String | No       | Sent as the LinkedIn commentary. If omitted, we reuse `title`. | `title` |
| visibility              | String | No       | `PUBLIC`, `CONNECTIONS`, `LOGGED_IN`, `CONTAINER`. Aliases: `linkedin_visibility`, `linkedinVisibility`. | "PUBLIC"    |
| target_linkedin_page_id | String | No       | LinkedIn page ID to upload videos to an organization | "107579166" |
| thumbnail | File | No | Custom thumbnail image for the video, uploaded through the [LinkedIn Videos API](https://learn.microsoft.com/en-us/linkedin/marketing/community-management/shares/videos-api) before the video is finalized. Accepts a multipart image file or a public URL. Formats: JPG/PNG. Max 10 MB. If both `thumbnail` (file) and `thumbnail_url` are provided, the file takes precedence. Setting the thumbnail is best-effort: if LinkedIn rejects it the video is still published and the reason is returned as `thumbnail_error`. | - |
| thumbnail_url | String (URL) | No | Alternative to provide the thumbnail as a public URL. | - |
| linkedin_disable_reshare | Boolean | No | When `true`, disable reshare. | false |
| linkedin_subtitles | File | No | Multipart `.srt` subtitles (English video). Alternatives: `linkedin_subtitles_url`, `linkedin_subtitles_text`. | - |

### YouTube

For more information about YouTube API parameters, visit the [YouTube Data API documentation](https://developers.google.com/youtube/v3/docs/videos?hl=es-419#resource).

| Name | Type | Required | Description | Default |
|------|------|----------|-------------|---------|
| youtube_title | String | No | Specific title for the YouTube video. Fallbacks to `title`. | `title` |
| youtube_description or description | String | No | Populates `snippet.description`. If omitted, we send `title`. | `title` |
| tags | Array | No | Array of tags | [] |
| categoryId | String | No | Video category | "22" |
| privacyStatus | String | No | Privacy setting ("public", "unlisted", "private") | "public" |
| embeddable | Boolean | No | Whether video is embeddable | true |
| license | String | No | Video license ("youtube", "creativeCommon") | "youtube" |
| publicStatsViewable | Boolean | No | Whether public stats are viewable | true |
| thumbnail | File | No | Custom thumbnail image to set after upload. Accepts a multipart image file or a public URL. Formats: JPG/PNG/GIF/BMP. Max 2 MB. If both `thumbnail` (file) and `thumbnail_url` are provided, the file takes precedence. To set or replace the thumbnail of a video that is already published, use [Set YouTube Thumbnail](./youtube-thumbnail.md). | - |
| thumbnail_url | String (URL) | No | Alternative to provide the thumbnail as a public URL. | - |
| selfDeclaredMadeForKids | Boolean | No | Explicit declaration that the video is made for children | false |
| containsSyntheticMedia | Boolean | No | Declaration that the video contains realistic altered or synthetic (AI-generated) content. Also accepted via the cross-platform `is_ai_generated` alias. | false |
| defaultLanguage | String | No | Language of title and description (BCP-47 code, e.g., "es", "en") | - |
| defaultAudioLanguage | String | No | Language of the video audio (BCP-47 code, e.g., "es-ES", "en-US") | - |
| allowedCountries | String | No | Comma-separated list of country codes where the video is allowed (e.g., "US,CA,MX") | - |
| blockedCountries | String | No | Comma-separated list of country codes where the video is blocked (e.g., "CN,RU") | - |
| hasPaidProductPlacement | Boolean | No | Declaration that the video includes paid product placements | false |
| recordingDate | String | No | Recording date and time of the video (ISO 8601 format, e.g., "2024-01-15T14:30:00Z") | - |
| youtube_playlist_id | String | No | One or more playlist IDs on the same channel to add the video to after it is published. Accepts a single ID (e.g., "PLxxxxxxxxxxxx") or a comma-separated list (e.g., "PLaaa,PLbbb"). Adding to a playlist is best-effort: if it fails the video stays published and the per-playlist outcome is reported under `playlists` in the response. | - |
| youtube_subtitle_file | File | No | Subtitle file to upload to the video. Supported formats: SRT, VTT, SBV, SUB, ASS, SSA, TTML, DFXP. Must be accompanied by `youtube_subtitle_language`. | - |
| youtube_subtitle_language | String | No | BCP-47 language code for the subtitle file (e.g., "en", "es", "fr"). Required when uploading subtitles. | - |
| youtube_subtitle_name | String | No | Display name for the subtitle track (e.g., "English", "Español"). Defaults to the language code if not provided. | - |
| `youtube_subtitle_file_{N}` | File | No | Indexed subtitle file for multiple tracks (N = 0, 1, 2...). Use with `youtube_subtitle_language_{N}`. | - |
| `youtube_subtitle_language_{N}` | String | No | Language code for indexed subtitle track N. | - |
| `youtube_subtitle_name_{N}` | String | No | Display name for indexed subtitle track N. | - |
| youtube_notify_subscribers | Boolean | No | Notify subscribers. | true |
| youtube_publish_at | String | No | RFC3339 timestamp. Forces `privacyStatus=private`. | - |

> **Note:** custom thumbnails require a verified YouTube channel. To set or replace one after publishing (for example on a Short), call [Set YouTube Thumbnail](./youtube-thumbnail.md).

**Notes about YouTube parameters:**
- **Subtitles:** You can upload multiple subtitle files by using indexed fields (`youtube_subtitle_file_0`, `youtube_subtitle_language_0`, `youtube_subtitle_file_1`, `youtube_subtitle_language_1`, etc.). Each subtitle file requires a corresponding language code. Subtitles are uploaded after the video is processed using the [YouTube Captions API](https://developers.google.com/youtube/v3/docs/captions).
- **Region restrictions:** `allowedCountries` and `blockedCountries` cannot be used simultaneously. Country codes must be ISO 3166-1 alpha-2 (e.g., "US", "CA", "MX").
- **Language settings:** `defaultLanguage` affects title and description display, while `defaultAudioLanguage` specifies the spoken language in the video. Use BCP-47 codes (e.g., "es" for Spanish, "es-ES" for Spain Spanish).
- **Legal declarations:** `selfDeclaredMadeForKids` is used for COPPA compliance. `containsSyntheticMedia` provides transparency for AI-generated content. `hasPaidProductPlacement` ensures FTC compliance.
- **Playlists:** `youtube_playlist_id` adds the video to one or more playlists **after** it has been published, using the [YouTube PlaylistItems API](https://developers.google.com/youtube/v3/docs/playlistItems/insert). Pass a single playlist ID or a comma-separated list of IDs. The playlists must belong to the same channel as the connected YouTube account. This step is non-fatal: if adding to a playlist fails (e.g., an invalid ID or a playlist owned by another channel), the video remains published and the outcome for each playlist is returned in the `playlists` array of the response (each entry includes `success` and `playlist_id`, plus `playlist_item_id` on success or `error` on failure).

### Facebook

For more information about Facebook API parameters, visit the [Facebook Graph API documentation](https://developers.facebook.com/docs/graph-api/reference/page/video_reels/?locale=es_ES) and the [Facebook Video API Publishing Guide](https://developers.facebook.com/docs/video-api/guides/publishing/).

| Name             | Type   | Required | Description                                                       | Default     |
|------------------|--------|----------|-------------------------------------------------------------------|-------------|
| facebook_title   | String | No       | Specific title for the Facebook post. Fallbacks to `title`. **Note:** If `facebook_media_type` is `"STORIES"`, this field is ignored.       | `title` |
| facebook_description or description      | String | No       | Sent as `description` for the video. **Note:** If `facebook_media_type` is `"STORIES"`, this field is ignored. | `title` |
| facebook_page_id | String | Yes      | Facebook Page ID where the video will be posted                   | -           |
| facebook_media_type | String | No | Type of media: `"REELS"` (short-form 9:16), `"STORIES"` (24h ephemeral), or `"VIDEO"` (normal page video, any aspect ratio, up to 4 hours) | "REELS" |
| video_state      | String | No       | Desired state of the video ("DRAFT", "PUBLISHED")    | "PUBLISHED" |
| thumbnail_url    | String | No       | Public URL of an image to set as the video thumbnail. Only supported when `facebook_media_type` is `"VIDEO"`. Uses the [Facebook Video Thumbnails API](https://developers.facebook.com/docs/graph-api/reference/video/thumbnails/#Creating). | -           |
| facebook_is_ai_generated | Boolean | No | AI-generated label on Reels. | - |
| facebook_unpublished_content_type | String | No | Video only: `DRAFT`, `INLINE_CREATED`, or `ADS_POST`. `SCHEDULED` is rejected. | - |
| facebook_no_story | Boolean | No | When `true`, do not publish a story from this post. | - |
| facebook_secret | Boolean | No | Unpublished/secret video. | - |
| facebook_collaborators | Page IDs (list, `[]`, or CSV) | No | Reels collaborator invitations. Response includes `collaborators.invited` / `collaborators.failed`. Meta limit: 10 invitations per Page per 24 h. | - |

Connecting Facebook only links the account; it does **not** pick a destination Page. Meta only allows posting to Pages, not personal profiles.

Pass `facebook_page_id` on every upload. If you omit it and exactly one Page is connected, that Page is used. If several are connected, the API returns `available_pages` so you can choose. Look up ids with [Get Facebook Pages](./get-facebook-pages.md), or [pin a Page](./facebook-page.md) once — a pinned Page takes precedence over `facebook_page_id`.

`facebook_media_type=VIDEO` is a normal Page video (any aspect ratio, up to 4 hours, custom `thumbnail_url`). Default is Reels. Stories ignore title and description.

### Threads

For more information about Threads API parameters, visit the [Threads API documentation](https://developers.facebook.com/docs/threads).

| Name | Type | Required | Description | Default |
|------|------|----------|-------------|---------|
| threads_title | String | No | Specific title for the Threads post. Fallbacks to `title`. | `title` |
| threads_topic_tag | String | No | A topic tag for the post (1-50 characters). Cannot contain periods (.) or ampersands (&). One tag per post. Helps increase reach. | - |
| threads_alt_text | String or `threads_alt_text[]` | No | Alt text, max 1,000 characters. | - |
| threads_reply_control | String | No | Who can reply: `everyone`, `accounts_you_follow`, `mentioned_only`, `parent_post_author_only`, `followers_only` (alias `followers`). | - |
| threads_reply_to_id | String | No | Numeric post ID to reply to. | - |
| threads_quote_post_id | String | No | Numeric post ID to quote. | - |

The global `description` field is ignored for Threads video uploads.

### X (Twitter)

:::warning URLs are stripped from every X post
Upload-Post removes every URL that X would turn into a clickable link from
the caption, title, and `first_comment` before sending the tweet — schemed
URLs (`https://`, `http://`, `ftp://`), `www.` hosts, shorteners (`t.co`,
`bit.ly`, …), bare hostnames with a path (`example.com/foo`), and IPs with a
path.

Obfuscated forms (`example[.]com`, `hxxp://`, unicode dots) are **not**
stripped because X does not parse them as links — they display as plain text
and are billed at the normal `$0.015` rate.

**Why:** X bills `$0.200` per post that contains a URL vs `$0.015` without —
13× more. Stripping happens on every X path (video, photo, text, scheduled
and retried). See the
[Character Limits page](../resources/character-limits.md#x-twitter-character-limits)
for details.
:::

For more information about X API parameters, visit the [X API Post Creation documentation](https://docs.x.com/x-api/posts/creation-of-a-post).

| Name                        | Type    | Required | Description                                                                                                                           | Default     |
|-----------------------------|---------|----------|---------------------------------------------------------------------------------------------------------------------------------------|-------------|
| x_title                      | String  | No       | Specific title for the tweet. Fallbacks to `title`.                                                                                   | `title`     |
| x_long_text_as_post          | Boolean | No       | When `true`, publishes long text as a single post. Otherwise, creates a thread.                                                      | `false`     |
| reply_settings               | String  | No       | Controls who can reply to the tweet ("following", "mentionedUsers", "subscribers", "verified")                                       | -           |
| geo_place_id                 | String  | No       | Place ID for adding geographic location to the tweet                                                                                  | -           |
| nullcast                     | Boolean | No       | Whether to publish without broadcasting (promotional/promoted-only posts)                                                             | `false`     |
| made_with_ai                 | Boolean | No       | Disclose that the post contains AI-generated media. Also accepted via the cross-platform `is_ai_generated` alias.                     | `false`     |
| for_super_followers_only     | Boolean | No       | Tweet exclusive for super followers                                                                                                   | `false`     |
| community_id                 | String  | No       | Community ID for posting to specific communities                                                                                      | -           |
| share_with_followers         | Boolean | No       | Share community post with followers                                                                                                   | `false`     |
| direct_message_deep_link     | String  | No       | Link to take the conversation from public timeline to private Direct Message                                                         | -           |
| tagged_user_ids              | Array   | No       | Array of user IDs to tag in the media (max 10 users)                                                                                  | []          |
| reply_to_id                  | String  | No       | ID of the tweet to reply to. Creates a reply to the specified tweet.                                                                  | -           |
| exclude_reply_user_ids       | Array   | No       | Array of user IDs to exclude from replying to this tweet. Requires `reply_to_id`.                                                    | []          |
| x_alt_text | String, `x_alt_text[]`, or JSON array | No | Alt text, max 1,000 characters. | - |
| x_subtitles_url | String | No | Public `.srt` URL, max 1 MB. Alternative: `x_subtitles` (inline text). | - |
| x_subtitles_language | String | No | Two-letter language code. | `EN` |
| x_subtitles_name | String | No | Display name, max 150 characters. | language |
| x_paid_partnership | Boolean | No | Paid partnership flag (applied to the first tweet). | false |

The global `description` field is ignored for X uploads.

`quote_tweet_id` cannot be used here: X treats media and quote tweets as mutually exclusive, so a video plus `quote_tweet_id` is rejected (`quote_tweet_id cannot be used when uploading media to X`). Quote via [Upload Text](./upload-text.md) and share the video as a separate tweet.

`reply_to_id` can return **HTTP 403** on X's Pay-Per-Use tier if the account has not engaged with that author. Reconnecting does not fix it — reply to someone the account already follows or has interacted with.

#### How X (Twitter) Thread Creation Works (Advanced Logic)

**Note:** The following describes the default thread creation logic. To override this and post long text as a single post, set the `x_long_text_as_post` parameter to `true`.

The system is engineered to create well-formatted, natural-looking threads on X (formerly Twitter). Instead of simply splitting text at every line break, it intelligently groups paragraphs to create more readable tweets.

Here's the step-by-step logic:

**Intelligent Paragraph Grouping (Primary Method):**

The function first identifies distinct paragraphs (any text separated by a blank line).
It then combines as many of these paragraphs as possible into a single tweet, filling it up to the 280-character limit without exceeding it. The double newline (`\n\n`) between combined paragraphs is preserved for formatting.
This results in fewer, more substantial tweets that flow naturally, just as if a person had written them.

**Handling Exceptionally Long Paragraphs:**

If a single paragraph is, by itself, longer than the 280-character limit, a more granular splitting logic is automatically triggered for that paragraph only:

- **Split by Line Break:** The system first attempts to break the paragraph down by its individual line breaks (`\n`).
- **Split by Word:** If any of those single lines are still too long, it will split them by words as a final resort.

**Media Attachment:**

For posts that include photos or videos, all media is attached only to the first tweet of the thread. The subsequent tweets in the thread will be text-only replies.

### Pinterest

| Name                                   | Type   | Required | Description                                                                              | Default |
|----------------------------------------|--------|----------|------------------------------------------------------------------------------------------|---------|
| pinterest_title                        | String | No       | Specific title for the Pinterest Pin. Fallbacks to `title`.                              | `title` |
| pinterest_description or description    | String | No       | Populates `pin.description`. If omitted, we reuse `title`.                                | `title` |
| pinterest_board_id                     | String | Yes       | Pinterest board ID to publish the video to.                                              | -       |
| pinterest_alt_text   | String | No       | Alt text for the video.                      | -       |
| pinterest_link                         | String | No       | Destination link for the video Pin.                                                      | -       |
| pinterest_cover_image_url              | String | No       | URL of an image to use as the video cover.                                               | -       |
| pinterest_cover_image_content_type     | String | No       | Content type of the cover image (e.g., image/jpeg, image/png), used if `pinterest_cover_image_data` is provided. | -       |
| pinterest_cover_image_data             | String | No       | Base64 encoded cover image data, used if `pinterest_cover_image_content_type` is provided. | -       |
| pinterest_cover_image_key_frame_time | Integer| No       | Cover frame time in **seconds** (values larger than the duration are treated as milliseconds). | -       |
| pinterest_board_section_id | String | No | Board section ID. | - |
| pinterest_ai_disclosures | CSV, JSON, or list | No | `AI_MODIFIED`, `SYNTHETIC_PERFORMER`. | - |

### Bluesky

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| bluesky_title | String | No | Specific text for the Bluesky video post. Fallbacks to `title`. | `title` |
| bluesky_alt_text | String, JSON array, or `\|\|`-separated | No | Alt text for the video. | - |
| bluesky_langs | String | No | Up to 3 BCP-47 codes, comma-separated. | - |
| bluesky_labels | String | No | Comma list of `porn`, `sexual`, `nudity`, `graphic-media`. | - |
| bluesky_threadgate | String | No | Who can reply. Alias: `bluesky_reply_settings`. | everyone |
| bluesky_postgate | String | No | `disable_quotes` (or `true`) to block quotes. Alias: `bluesky_quote_settings`. | quotes allowed |
| bluesky_quote_uri | String | No | Post URL or `at://` URI to quote. Aliases: `bluesky_quote_id`, `bluesky_quote_url`. | - |

Note: Video uploads to Bluesky are limited to 10 GB per day/user and 25 videos per day, **300 MB** maximum, and up to **10 minutes** in duration. Supported formats: .mp4, .mpeg, .webm, .mov.

### Reddit

:::warning Reddit posting is currently unavailable
Uploads with `platform[]=reddit` return **HTTP 503** with `error_code: "reddit_unavailable"`. Do not include `reddit` in `platform[]` until posting is restored. OAuth connect and comments with `platform=reddit` return the same error.
:::

```json
{
  "success": false,
  "error_code": "reddit_unavailable",
  "error": "Reddit posting is currently unavailable."
}
```

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| reddit_title | String | No | Specific title for the Reddit post. Fallbacks to `title`. Unused while Reddit is unavailable. | `title` |
| subreddit | String | Yes | Name of the subreddit to post to (without "r/"). Unused while Reddit is unavailable. | - |
| flair_id | String | No | ID of the flair to apply to the post. Unused while Reddit is unavailable. | - |

### Discord

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| discord_title | String | No | Caption sent alongside the video. Fallbacks to `title`. | `title` |
| discord_alt_text | String | No | Alt text for the video. | - |
| discord_thread_id | String | No | Existing thread ID. | - |
| discord_max_file_mb | Integer | No | Per-file cap in MiB (1–500). Files over 20 MiB are rejected unless raised. | 20 |

Note: Discord uploads the video as a message attachment to the channel behind the connected webhook (see [Connecting Discord](../guides/connecting-accounts.md#connecting-discord-manual-credentials)). The optional caption is limited to 2000 characters. File size is bounded by the limit of the target Discord server.

### Telegram

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| telegram_title | String | No | Caption sent alongside the video. Fallbacks to `title`. | `title` |
| telegram_parse_mode | String | No | `MarkdownV2` or `HTML`. | - |
| telegram_has_spoiler | Boolean | No | Mark media as spoiler. | - |
| telegram_as_document | Boolean | No | Send as a document instead of video. | - |

Note: Your connected bot delivers the video to the configured chat/channel via `sendVideo` (see [Connecting Telegram](../guides/connecting-accounts.md#connecting-telegram-manual-credentials)). The optional caption is limited to **1024 characters**.

### Mastodon

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| mastodon_title | String | No | Caption for the Mastodon post. Fallbacks to `title`. | `title` |
| mastodon_visibility | String | No | `public`, `unlisted`, `private`, or `direct`. | - |
| mastodon_sensitive | Boolean | No | Mark as sensitive. | - |
| mastodon_alt_text | String | No | Alt text for the video. | - |

Note: The video is uploaded to your instance (async processing) and attached to the status once ready (see [Connecting Mastodon](../guides/connecting-accounts.md#connecting-mastodon-manual-credentials)).

### WordPress

| Name | Type | Required | Description | Default |
|---|---|---|---|---|
| wordpress_title | String | No | Post title. Fallbacks to `title`. | `title` |
| wordpress_status | String | No | `publish`, `draft`, `pending`, or `future`. | - |
| wordpress_alt_text | String | No | Media alt text. | - |

Note: The video is uploaded to the WordPress media library and embedded in the post (see [Connecting WordPress](../guides/connecting-accounts.md#connecting-wordpress-manual-credentials)).

### Google Business Profile

| Name | Type | Required | Description | Default |
|------|------|----------|-------------|---------|
| gbp_location_id | String | No* | The location to post to. Use [Get Google Business Locations](./get-google-business-locations.md) to list available locations. | Auto |
| gbp_topic_type | String | No | Post type: `STANDARD` (default), `EVENT`, or `OFFER`. | `STANDARD` |
| gbp_cta_type | String | No | Call-to-action button: `BOOK`, `ORDER`, `SHOP`, `LEARN_MORE`, `SIGN_UP`, `CALL`. Alias: `cta_type`. Other spellings (`cta_action_type`, `gbp_cta_action_type`) are accepted too, but the canonical name is `gbp_cta_type`. An unknown value is rejected with `400` instead of publishing the post without its button. | - |
| gbp_cta_url | String | Conditional | URL the button opens. Required for every type except `CALL`, which dials the location's phone number and must not carry a URL. Missing URL → `400`. Alias: `cta_url`. | - |
| gbp_post_type | String | No | Set to `MEDIA` (also accepts `PHOTO` or `GALLERY`) to publish an attached photo to the location's **Media/Gallery** tab instead of creating a Local Post. See [Publishing to the Media/Gallery tab](./upload-photo.md#publishing-to-the-mediagallery-tab). | - |
| gbp_upload_to_gallery | Boolean | No | Alternative to `gbp_post_type`. Set to `true` for the same Media/Gallery behaviour. | `false` |
| gbp_media_category | String | No | Category assigned to the uploaded photo in the Media tab. Alias: `media_category`. Allowed: `COVER`, `PROFILE`, `LOGO`, `EXTERIOR`, `INTERIOR`, `PRODUCT`, `AT_WORK`, `FOOD_AND_DRINK`, `MENU`, `COMMON_AREA`, `ROOMS`, `TEAMS`, `ADDITIONAL`. | `ADDITIONAL` |

If `gbp_location_id` is omitted and the account has exactly one location, that location is used. If several are connected, the API asks you to pick one.

The Media/Gallery flow publishes a **photo**. Selecting it without media returns `400` (`error_code: MEDIA_REQUIRED`). Omitting `gbp_post_type` keeps Local Post behaviour.

Google documents a 75 MB limit for Local Post video, but its API refuses much smaller files. Videos above 12 MB are re-encoded automatically (target ~10 MB) and the result includes a `warnings` entry. Clips longer than 30 seconds, or still over 28 MB after re-encode, are rejected with `error_code: MEDIA_LIMITS`.

**Event parameters** (when `gbp_topic_type` is `EVENT`):

| Name | Type | Required | Description |
|------|------|----------|-------------|
| gbp_event_title | String | Yes | Title of the event. |
| gbp_event_start_date | String | Yes | Start date in `YYYY-MM-DD` format. |
| gbp_event_start_time | String | No | Start time in `HH:MM` format (24h). |
| gbp_event_end_date | String | Yes | End date in `YYYY-MM-DD` format. |
| gbp_event_end_time | String | No | End time in `HH:MM` format (24h). |

**Offer parameters** (when `gbp_topic_type` is `OFFER`):

| Name | Type | Required | Description |
|------|------|----------|-------------|
| gbp_coupon_code | String | No | Coupon or promo code. Alias of `gbp_offer_coupon` (old name wins if both are sent). |
| gbp_redeem_url | String | No | URL where the offer can be redeemed. Alias of `gbp_offer_redeem_url`. |
| gbp_terms | String | No | Terms and conditions of the offer. Alias of `gbp_offer_terms`. |
| gbp_language_code | String | No | BCP-47 language. Default `"en"`. |

### Example Requests

### Upload a Video to TikTok

<Tabs groupId="lang">
<TabItem value="curl" label="cURL">

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'video=@/path/to/your/video.mp4' \
  -F 'title="Your Video Title"' \
  -F 'user="test"' \
  -F 'platform[]=tiktok' \
  -X POST https://api.upload-post.com/api/upload
```

</TabItem>
<TabItem value="python" label="Python">

```python
import requests

response = requests.post(
    "https://api.upload-post.com/api/upload",
    headers={"Authorization": "Apikey your-api-key-here"},
    files={"video": open("/path/to/your/video.mp4", "rb")},
    data={
        "title": "Your Video Title",
        "user": "test",
        "platform[]": "tiktok",
    },
)
print(response.json())
```

</TabItem>
<TabItem value="js" label="JavaScript">

```javascript

const form = new FormData();
form.append("video", new Blob([fs.readFileSync("/path/to/your/video.mp4")]), "video.mp4");
form.append("title", "Your Video Title");
form.append("user", "test");
form.append("platform[]", "tiktok");

const response = await fetch("https://api.upload-post.com/api/upload", {
  method: "POST",
  headers: { Authorization: "Apikey your-api-key-here" },
  body: form,
});
console.log(await response.json());
```

</TabItem>
</Tabs>

### Upload a Video to YouTube Using URL

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'video="https://example.com/videos/myvideo.mp4"' \
  -F 'title="Your Video Title"' \
  -F 'description="Your video description"' \
  -F 'user="test"' \
  -F 'platform[]=youtube' \
  -F 'tags[]=tutorial' \
  -F 'tags[]=howto' \
  -F 'categoryId="22"' \
  -X POST https://api.upload-post.com/api/upload
```

### Upload a Video to YouTube With Custom Thumbnail

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'video=@/path/to/your/video.mp4' \
  -F 'title="Your Video Title"' \
  -F 'description="Your video description"' \
  -F 'user="test"' \
  -F 'platform[]=youtube' \
  -F 'thumbnail_url="https://example.com/images/thumbnail-1280x720.jpg"' \
  -X POST https://api.upload-post.com/api/upload
```

### Upload to YouTube with thumbnail file

```bash
curl -X POST https://api.upload-post.com/api/upload \
  -H "Authorization: Apikey <API_KEY>" \
  -F "user=<profile_username>" \
  -F "platform[]=youtube" \
  -F "title=Demo video" \
  -F "description=Description" \
  -F "video=@/path/video.mp4;type=video/mp4" \
  -F "thumbnail=@/path/thumbnail.jpg;type=image/jpeg"
```

### Upload to YouTube with subtitle files

```bash
curl -X POST https://api.upload-post.com/api/upload \
  -H "Authorization: Apikey <API_KEY>" \
  -F "user=<profile_username>" \
  -F "platform[]=youtube" \
  -F "title=Demo video with subtitles" \
  -F "description=Video with English and Spanish subtitles" \
  -F "video=@/path/video.mp4;type=video/mp4" \
  -F "youtube_subtitle_file_0=@/path/subtitles_en.srt" \
  -F "youtube_subtitle_language_0=en" \
  -F "youtube_subtitle_name_0=English" \
  -F "youtube_subtitle_file_1=@/path/subtitles_es.srt" \
  -F "youtube_subtitle_language_1=es" \
  -F "youtube_subtitle_name_1=Español"
```

### Upload to YouTube and add to playlists

```bash
curl -X POST https://api.upload-post.com/api/upload \
  -H "Authorization: Apikey <API_KEY>" \
  -F "user=<profile_username>" \
  -F "platform[]=youtube" \
  -F "title=Demo video added to playlists" \
  -F "description=Video that is added to one or more playlists after publishing" \
  -F "video=@/path/video.mp4;type=video/mp4" \
  -F "youtube_playlist_id=PLaaaaaaaaaaaa,PLbbbbbbbbbbbb"
```

### Responses

- 200 OK (synchronous, finished fast)

```json
{
  "success": true,
  "results": {
    "instagram": {
      "success": true,
      "url": "https://instagram.com/p/...",
      "container_id": "1789...",
      "video_was_transcoded": true,
      "changes": {},
      "prevalidation_metadata": {}
    },
    "linkedin": {
      "success": false,
      "error": "Expired access token"
    }
  },
  "usage": {
    "count": 12,
    "limit": 100,
    "last_reset": "2025-09-01T10:00:00.000Z"
  }
}
```

- 200 OK (asynchronous/background started, including sync→background fallback)

```json
{
  "success": true,
  "message": "Upload initiated successfully in background.",
  "request_id": "1a2b3c4d5e...",
  "total_platforms": 3
}
```

- 202 Accepted (scheduled)

```json
{
  "success": true,
  "job_id": "scheduler_job_123",
  "scheduled_date": "2025-09-22T10:00:00Z"
}
```

- 400 Bad Request
  - Missing `user`, `platform[]`, video file/URL, invalid `scheduled_date`, invalid platform values, Pinterest without `pinterest_board_id`.

```json
{ "success": false, "message": "Username required in form data" }
```

- 401 Unauthorized

```json
{ "success": false, "message": "Invalid or expired token" }
```

- 403 Forbidden (e.g., TikTok on Free plan)

```json
{ "success": false, "message": "TikTok uploads are not available on the Free plan. Please upgrade to a paid plan." }
```

- 404 Not Found (e.g., user not found after auth)

```json
{ "success": false, "message": "User not found" }
```

- 429 Too Many Requests (monthly limit exceeded; includes current usage)

```json
{
  "success": false,
  "message": "This upload would exceed your monthly limit.",
  "usage": { "count": 10, "limit": 10, "last_reset": "..." }
}
```

- 500 Internal Server Error

```json
{ "success": false, "error": "Detailed error message" }
```

Notes
- When async or when sync falls back to background, use `GET /api/uploadposts/status?request_id={request_id}` to poll progress.
- Per-platform results may include: `url`, `publish_id`, `container_id`, `post_id`, `video_urn`, `video_reel_id`, `video_id`, `image_urns`, `post_ids`, `video_was_transcoded`, `changes`, `prevalidation_metadata`, or `error`. A result with `skipped: true` means the profile has no account for that platform (see [Unconnected platforms](#unconnected-platforms)).

### Unconnected platforms

Each profile only has accounts for the platforms you connected to it. When a request lists several platforms and the profile has **no account for some of them**, the upload is **not rejected**: the connected platforms are published normally and each unconnected one comes back in `results` as *skipped* (nothing is posted there and it is not counted as a failure in your dashboard):

```json
{
  "success": true,
  "results": {
    "instagram": { "success": true, "url": "https://instagram.com/p/..." },
    "linkedin": {
      "success": false,
      "skipped": true,
      "skip_reason": "profile_platform_not_configured",
      "error": "Profile creator has no Linkedin account configured",
      "error_code": "profile_platform_mapping_invalid",
      "failure_stage": "profile_platform_validation"
    }
  }
}
```

For asynchronous uploads the same per-platform entry is returned by [`GET /api/uploadposts/status`](./upload-status.md), and skipped platforms count as finished, so `completed` reaches `total` as soon as the connected platforms are done.

If **none** of the requested platforms is connected to the profile, the request is rejected with `400`:

```json
{
  "success": false,
  "message": "None of the requested platforms are valid for profile \"creator\". Profile creator has no Linkedin account configured",
  "invalid_platforms": { "linkedin": "Profile creator has no Linkedin account configured" }
}
```

**Scheduled posts** (`scheduled_date`) keep **every** requested platform, connected or not, and are validated again when the job runs. Connect the missing account before the scheduled time and the post will be published there too; otherwise that platform is reported as skipped at execution time.


---
# User Profiles API
URL: https://docs.upload-post.com/api/user-profiles

# User Profiles API

These endpoints allow you to integrate Upload-Post directly into your platform by managing user profiles and generating secure tokens for account linking.

See the [User Profile Integration Guide](../guides/user-profile-integration.md) for a conceptual overview and workflow.

## Authentication

All API requests require authentication using your API Key. Include it in the `Authorization` header for every request:

```
Authorization: Apikey YOUR_API_KEY
```

Replace `YOUR_API_KEY` with the actual API key provided to you.

---

## User Profile Management

Manage user profiles within Upload-Post that correspond to users on your platform.

### Endpoint

```
/api/uploadposts/users
```

---

### Create User Profile

Creates a new profile linked to a user on your platform.

*   **Method:** `POST`
*   **Headers:**
    *   `Authorization: Apikey YOUR_API_KEY`
    *   `Content-Type: application/json`
*   **Body Parameters:**

    | Name     | Type   | Required | Description                                                                 |
    |----------|--------|----------|-----------------------------------------------------------------------------|
    | username | String | Yes      | A unique identifier for the user on your platform (e.g., your internal ID). |

*   **Example Body:**
    ```json
    {
      "username": "your_platform_user_id_123"
    }
    ```
*   **Success Response (201 Created):**
    ```json
    {
      "profile": {
        "created_at": "Fri, 02 May 2025 21:43:14 GMT",
        "social_accounts": {
          "tiktok": ""
          // Other platforms will appear here as they are connected
        },
        "username": "your_platform_user_id_123"
      },
      "success": true
    }
    ```
    *   `profile`: Contains details of the newly created profile.
        *   `created_at`: Timestamp of profile creation.
        *   `social_accounts`: Object showing connected accounts (initially empty or with placeholders).
        *   `username`: The unique identifier provided.
    *   `success`: Indicates successful creation.
*   **Error Responses:**
    *   `400 Bad Request`: Missing or invalid `username`.
    *   `401 Unauthorized`: Invalid or missing API Key.
    *   `403 Forbidden`: Profile limit reached for the current plan (`error_code: PROFILE_LIMIT_REACHED`).
    *   `409 Conflict`: A profile with the provided `username` already exists.

---

### Get User Profiles

Retrieves a list of all user profiles created under your API key.

*   **Method:** `GET`
*   **Headers:**
    *   `Authorization: Apikey YOUR_API_KEY`
*   **Query Parameters:** None
*   **Success Response (200 OK):**
    ```json
    {
        "limit": 10,
        "plan": "default",
        "profiles": [
            {
                "created_at": "2025-04-02T17:44:33.229755",
                "social_accounts": {
                    "facebook": {
                        "display_name": "FB User",
                        "social_images": "url_to_fb_image"
                    },
                    "instagram": {
                        "display_name": "IG User",
                        "social_images": "url_to_ig_image"
                    }
                    // ... other connected platforms with their details
                },
                "username": "your_platform_user_id_1"
            },
            {
                "created_at": "Fri, 02 May 2025 21:43:14 GMT",
                "social_accounts": {
                    "tiktok": "" // Example of a platform added but not yet connected
                },
                "username": "your_platform_user_id_2"
            }
        ],
        "success": true
    }
    ```
    *   `limit`: The maximum number of profiles allowed by the current plan.
    *   `plan`: The subscription plan associated with the API key.
    *   `profiles`: An array of user profile objects.
        *   `created_at`: Timestamp of profile creation.
        *   `social_accounts`: An object detailing connected social media accounts. Each key is the platform name (e.g., `facebook`, `instagram`, `tiktok`). The value can be an object with details (`username`, `handle`, `display_name`, `social_images`) or an empty string/null if not fully connected. See [Identifying a connected account](#identifying-a-connected-account).
        *   `capabilities`: On the accounts that expose it (today, `tiktok`), the list of optional features that **this particular connection** supports. See [Account capabilities](#account-capabilities).
        *   `reauth_required`: `true` when the connection's token has expired and the account must be reconnected before it can publish.
        *   `username`: The unique identifier for the profile.
    *   `success`: Indicates successful retrieval.
*   **Error Responses:**
    *   `401 Unauthorized`: Invalid or missing API Key.

---

### Identifying a connected account

Every connected account in `social_accounts` carries two different strings, and
they answer two different questions:

| Field | What it is | Use it to |
| :--- | :--- | :--- |
| `username` | The **account identifier**: the platform's own id for the account, and the key this connection is stored under. Opaque by design. | Bind your own records to a destination. |
| `handle` | The public @name on the platform. | Show a human which account a post goes to. |
| `display_name` | The profile's display name. | Labels in your UI. |

For most platforms the identifier is a numeric id (Instagram's IG user id,
Facebook's page id, YouTube's channel id). For **TikTok** it is the account's
`open_id`, which looks like `-000g91dgwXgwtNwc-V8dolLsQJMUY3MQAfv`. That is the
identifier TikTok itself issues; it is **scoped to the application**, so the
same TikTok account has a different `open_id` in every app that connects to it,
including ours. Within Upload-Post it is stable:

*   it does **not** change when the user reconnects the same account,
*   it does **not** change when the user renames the account or changes its
    @handle — `handle` follows the rename on the next reconnection, `username`
    never moves,
*   it **does** differ if a different TikTok account is connected. A changed
    `username` therefore means "this is another account", which is exactly the
    signal you want before publishing.

:::note Reading it back
`GET /api/uploadposts/users` (all profiles) and
`GET /api/uploadposts/users/{profile}` (one profile) are the supported
read-only endpoints. Both return the same `social_accounts` shape, and neither
publishes or modifies anything.
:::

---

### Account capabilities

Not every connected account supports the same optional fields. A connected
account can report what it supports in a `capabilities` array, so you can check
it **before** sending optional fields instead of guessing:

```json
{
    "success": true,
    "profiles": [
        {
            "username": "your_profile",
            "social_accounts": {
                "tiktok": {
                    "display_name": "Fotoexamen",
                    "handle": "fotoexamen",
                    "social_images": "https://storage.googleapis.com/.../avatar.jpg",
                    "reauth_required": false,
                    "capabilities": [
                        "music",
                        "location",
                        "cover_image",
                        "cover_timestamp",
                        "draft",
                        "video_privacy",
                        "photo_privacy",
                        "profile_analytics",
                        "comments",
                        "trend_search"
                    ]
                }
            }
        }
    ]
}
```

**TikTok capability values**

| Capability | What it unlocks |
| :--- | :--- |
| `music` | Attach a Commercial Music Library track: `tiktok_music_id`, `tiktok_music_volume`, `tiktok_music_start`, `tiktok_music_end`, `tiktok_original_sound_volume`. See [Get TikTok Trending Music](./get-tiktok-music.md). |
| `location` | Tag a place on the post: `tiktok_location_id` + `tiktok_location_name`. See [Get TikTok Locations](./get-tiktok-locations.md). |
| `cover_image` | Set a custom cover image: `tiktok_cover_image_url` / `tiktok_cover_image`. |
| `cover_timestamp` | Pick the cover frame with `cover_timestamp` (milliseconds). |
| `draft` | Send the video to the account's drafts with `post_mode=MEDIA_UPLOAD` (alias `tiktok_upload_to_draft=true`). |
| `photo_privacy` | The connection accepts `privacy_level` on **photo** posts. |
| `video_privacy` | The connection accepts `privacy_level` on **video** posts. Connections without it always publish videos as public — use `draft` instead. |
| `inbox_fallback` | When TikTok's daily active-user cap is hit, the post is delivered to the account's TikTok inbox as a draft instead of failing. See [Reached Active User Cap](../guides/reached-active-user-cap-error.md). |
| `comments` | Everything in the [Comments API](./comments.md) with `platform=tiktok`: list, create, reply, delete, the [replies under a comment](./comments.md#list-comments) (`comment_id`) and the [hide / like / pin](./comments.md#hide-like-or-pin-a-comment) verbs — plus attaching a [`first_comment`](./upload-video.md#platform-specific-first-comments) to a post. **Only granted on reconnection** — see below. |
| `trend_search` | [`GET /suggestions?type=keywords`](./suggestions.md#typekeywords): the terms people search around a word. **Only granted on reconnection** — see below. |
| `profile_analytics` | Profile-level TikTok analytics in [Get Analytics](./get-analytics.md) — including the [full per-post breakdown](./get-analytics.md#tiktok-the-full-per-post-breakdown) (retention, impression sources, audience types) — plus [Audience Insights](./audience.md) with its category benchmark, and [`GET /suggestions?type=hashtags`](./suggestions.md#typehashtags). Granted by **any recent connection**; no reconnection needed. |

`video_privacy` and `inbox_fallback` on one side and `music` / `location` /
`cover_image` / `draft` / `profile_analytics` on the other are
mutually exclusive: a connection reports one group or the other, never both.

Neither group changes how you ask for a draft. `post_mode=MEDIA_UPLOAD` (and its
alias `tiktok_upload_to_draft=true`) works on every TikTok connection, whether it
reports `draft` or `inbox_fallback` — do not branch on the capability list.
`inbox_fallback` is not a mode you can request: it is what happens on its own
when TikTok's daily active-user cap is hit.

:::caution `comments` and `trend_search` only appear after a reconnection
Every other capability depends on how the account was connected. These two
depend on a permission TikTok grants **at the moment of connecting**, and it
only started issuing it recently. An account connected before that publishes
exactly as it always did — nothing about its uploads changes — but it will not
report `comments` or `trend_search`, and the endpoints that need them answer
`400` with `error_code: "tiktok_reconnect_required"`.

The fix is the same one-click reconnection as any other missing capability: the
account owner reconnects TikTok from
[Manage Users](https://app.upload-post.com/manage-users), or you send them
through a [white-label connect link](../guides/user-profile-integration.md).
Nothing in your integration changes.

This is also why `first_comment` on TikTok can come back as a warning instead of
a comment: without `comments` the post is still published, and the response says
the first comment was skipped.
:::

**Sending a field your connection does not support is safe.** The post is still
published: the upload response comes back with a `warnings` array of plain
strings naming the field that was ignored and telling you that reconnecting the
TikTok account enables it.

```json
{
    "success": true,
    "warnings": [
        "tiktok_music_id ignored: your current TikTok connection cannot attach a track from TikTok's music catalogue. Reconnect your TikTok account from Manage Users (https://app.upload-post.com/manage-users) to enable it."
    ]
}
```

To enable a missing capability, reconnect the account from
[Manage Users](https://app.upload-post.com/manage-users) (or send your end user
through a [white-label connect link](../guides/user-profile-integration.md)).
Nothing in your integration changes — same endpoints, same field names.

:::note Treat `capabilities` as an open list
New values can be added over time. Check whether the capability you need is
present rather than matching the array exactly, and fall back gracefully when a
capability is missing.
:::

---

### Get a Specific User Profile

Retrieves information for a single user profile using its username.

*   **Method:** `GET`
*   **Endpoint**: `/api/uploadposts/users/{username}`

**Path Parameters**

| Parameter  | Type   | Description                                            |
| :--------- | :----- | :----------------------------------------------------- |
| `username` | string | **Required**. The username of the profile to retrieve. |

**Success Response (200 OK)**

If the profile is found, the API will return a JSON object with the profile details.

```json
{
    "success": true,
    "profile": {
        "created_at": "2023-10-27T10:00:00Z",
            "social_accounts": {
                "tiktok": {
                    "username": "tiktok_user_123",
                    "display_name": "User Display Name",
                    "social_images": "https://example.com/image.jpg"
                },
                "bluesky": {
                     "username": "user.bsky.social",
                     "display_name": "Bluesky User",
                     "social_images": "https://example.com/avatar.jpg"
                },
                "instagram": null
            },
        "username": "specific_profile_name"
    }
}
```

**Error Response (404 Not Found)**

If no profile is found with the specified `username`, the API will return:

```json
{
    "success": false,
    "message": "Profile not found"
}
```

---

### Delete User Profile

Deletes an existing user profile and its associated data (like social connections).

*   **Method:** `DELETE`
*   **Headers:**
    *   `Authorization: Apikey YOUR_API_KEY`
    *   `Content-Type: application/json`
*   **Body Parameters:**

    | Name     | Type   | Required | Description                                      |
    |----------|--------|----------|--------------------------------------------------|
    | username | String | Yes      | The unique identifier of the profile to delete. |

*   **Example Body:**
    ```json
    {
      "username": "user_id_to_delete"
    }
    ```
*   **Success Response (200 OK):**
    ```json
    {
      "message": "Perfil eliminado correctamente",
      "success": true
    }
    ```
*   **Error Responses:**
    *   `400 Bad Request`: Missing or invalid `username`.
    *   `401 Unauthorized`: Invalid or missing API Key.
    *   `404 Not Found`: No profile found with the provided `username`.

---

## JWT Management

Generate and validate JWTs for the secure social account linking process.

### Endpoint: Generate JWT URL

```
/api/uploadposts/users/generate-jwt
```

Generates a secure, single-use URL containing a JWT. Your user visits this URL to link their social media accounts.

*   **Method:** `POST`
*   **Headers:**
    *   `Authorization: Apikey YOUR_API_KEY`
    *   `Content-Type: application/json`
*   **Body Parameters:**

    | Name         | Type    | Required | Description                                                                                      |
    |--------------|---------|----------|--------------------------------------------------------------------------------------------------|
    | username     | String  | Yes      | The identifier for the user profile for which the JWT is being generated.                        |
    | redirect_url | String  | No       | (Optional) The URL to which the user will be redirected after linking their social account.      |
    | logo_image   | String  | No       | (Optional) A URL to a logo image to display on the linking page for branding purposes.           |
    | redirect_button_text | String | No | (Optional) The text to display on the redirect button after linking. Defaults to "Logout connection". |
    | connect_title | String | No | (Optional) Custom title text for the connection page. Defaults to "Connect Social Media Accounts". |
    | connect_description | String | No | (Optional) Custom description text for the connection page. Defaults to "Connect your social media accounts to manage your posts.". |
    | platforms    | Array   | No       | (Optional) List of platforms to show for connection. Possible values: 'tiktok', 'instagram', 'linkedin', 'youtube', 'facebook', 'x', 'threads', 'google_business'. Defaults to all supported platforms. |
    | show_calendar | Boolean | No       | (Optional) Whether to show the calendar view on the connection page. Defaults to `true`.         |
    | readonly_calendar | Boolean | No   | (Optional) When `true`, shows only a read-only calendar view. The user cannot edit, delete, or create posts, and cannot connect or disconnect social accounts. Ideal for sharing a content calendar with end clients. Defaults to `false`. |
    | language | String | No | (Optional) Forces the language of the connection page for this profile. Supported values: `en`, `es`, `de`, `fr`, `pt`, `pl`, `tr`. When omitted, the page automatically detects the visitor's browser language and falls back to English. |
    | ui_labels | Object | No | (Optional) Flat object of i18n key → replacement string, to override individual pieces of connect-page UI text. See [Custom UI Labels](#custom-ui-labels). |

*   **Supported languages:**

    | Value | Language   |
    |-------|------------|
    | `en`  | English    |
    | `es`  | Spanish    |
    | `de`  | German     |
    | `fr`  | French     |
    | `pt`  | Portuguese |
    | `pl`  | Polish     |
    | `tr`  | Turkish    |

*   **Example Body:**
    ```json
    {
      "username": "your_platform_user_id_123"
    }
    ```
*   **Success Response (200 OK):**
    ```json
    {
      "access_url": "https://app.upload-post.com/connect?token=GENERATED_JWT_TOKEN",
      "success": true,
      "duration": "48h"
    }
    ```
    *   `access_url`: The secure URL your user needs to visit. Redirect your user to this URL.
    *   `success`: Always `true` if the request was successful.
    *   `duration`: The validity period of the generated JWT (48 hours).
*   **Example Request (curl):**
    ```bash
    curl -X POST https://api.upload-post.com/api/uploadposts/users/generate-jwt \
      -H "Authorization: Apikey YOUR_API_KEY" \
      -H "Content-Type: application/json" \
      -d '{"username": "your_platform_user_id_123"}'
    ```
*   **Example Request with Calendar Disabled (curl):**
    ```bash
    curl -X POST https://api.upload-post.com/api/uploadposts/users/generate-jwt \
      -H "Authorization: Apikey YOUR_API_KEY" \
      -H "Content-Type: application/json" \
      -d '{"username": "your_platform_user_id_123", "show_calendar": false}'
    ```
*   **Example Request with Read-Only Calendar for Clients (curl):**
    ```bash
    curl -X POST https://api.upload-post.com/api/uploadposts/users/generate-jwt \
      -H "Authorization: Apikey YOUR_API_KEY" \
      -H "Content-Type: application/json" \
      -d '{"username": "your_platform_user_id_123", "readonly_calendar": true, "logo_image": "https://youragency.com/logo.png", "connect_title": "Your Content Calendar"}'
    ```
    This generates a link where the client only sees the calendar with scheduled posts (social channel, date/time, visual, text) but cannot edit anything or access other sections.
*   **Example Success Response (200 OK):**
    ```json
    {
      "access_url": "https://app.upload-post.com/connect?token=GENERATED_JWT_TOKEN_STRING",
      "duration": "48h",
      "success": true
    }
    ```
*   **Calendar Deep Link:** If you want users to land directly on the shared calendar view, replace the path with `/connect/calendar` while keeping the token intact, e.g. `https://app.upload-post.com/connect/calendar?token=GENERATED_JWT_TOKEN`. The page will automatically fall back to `/connect` when the profile has `show_calendar` disabled. When `readonly_calendar` is `true`, the user is automatically redirected to the calendar view regardless of the URL path.

*   **Error Responses:**
    *   `400 Bad Request`: Missing or invalid `username`, or an invalid `ui_labels` payload (see [Custom UI Labels](#custom-ui-labels)).
    *   `401 Unauthorized`: Invalid or missing API Key.
    *   `403 Forbidden`: Profile exists but is blocked by plan limits (`error_code: PROFILE_BLOCKED`).
    *   `404 Not Found`: No profile found with the provided `username` (`error_code: PROFILE_NOT_FOUND`).

*   **Integration tip:** If JWT generation returns `404`, call `GET /api/uploadposts/users` first to confirm the profile exists and that profile creation did not fail due to plan limits.

#### Custom UI Labels {#custom-ui-labels}

`connect_title`, `connect_description` and `redirect_button_text` cover the three most visible strings on the connection page. `ui_labels` goes further: it lets a white-label integration override **any individual piece of connect-page UI text**, in any language, without waiting for a translation to ship.

`ui_labels` is a **flat** object mapping the connect page's own i18n dot-path keys to the replacement strings:

```json
{
  "username": "your_platform_user_id_123",
  "language": "tr",
  "ui_labels": {
    "connect.connectButton": "Bağlan",
    "connect.notConnected": "Bağlı değil"
  }
}
```

*   **Example Request (curl):**
    ```bash
    curl -X POST https://api.upload-post.com/api/uploadposts/users/generate-jwt \
      -H "Authorization: Apikey YOUR_API_KEY" \
      -H "Content-Type: application/json" \
      -d '{
        "username": "your_platform_user_id_123",
        "language": "tr",
        "ui_labels": {
          "connect.connectButton": "Bağlan",
          "connect.notConnected": "Bağlı değil"
        }
      }'
    ```

**Validation rules**

| Rule | Limit |
|------|-------|
| Maximum number of entries | 100 |
| Key format | Must match `^[a-zA-Z0-9_.]+$` — letters, numbers, dots and underscores only |
| Value type | Must be a string |
| Value length | Maximum 300 characters |

A violation returns `400 Bad Request` with the specific reason:

```json
{
  "success": false,
  "message": "Invalid ui_labels key 'bad key!'. Keys may only contain letters, numbers, dots and underscores"
}
```

```json
{
  "success": false,
  "message": "ui_labels['connect.x'] must be a string"
}
```

**Update semantics**

`ui_labels` is stored on the profile and persists across JWT generations:

| You send | Result |
|----------|--------|
| The field is **omitted** | Previously stored labels are left **untouched** |
| `"ui_labels": { ... }` (non-empty) | Replaces the stored labels with what you sent |
| `"ui_labels": null` | **Clears** all stored labels |
| `"ui_labels": {}` | **Clears** all stored labels |

The stored labels are returned inside the `profile` object by [`GET /api/uploadposts/users/validate-jwt`](#endpoint-validate-jwt), so you can read back what is currently applied.

:::warning
The keys are the **connect page's own translation keys**, not free-form identifiers. An unknown key is simply never rendered. Override only keys you have confirmed exist — verify each one against the live connect page after setting it.
:::

#### Mobile OAuth Compatibility

When users open the connection page on a mobile device (iOS or Android), the
operating system may intercept OAuth URLs (e.g. `instagram.com`, `accounts.google.com`)
and open the corresponding native app instead of keeping the flow in the browser.
Because native apps cannot handle the OAuth authorization URL, the connection fails.

Upload-Post automatically detects mobile browsers and routes OAuth redirects through
a secure intermediate page (`/api/uploadposts/oauth/bounce`) that performs the
redirect via JavaScript. This bypasses Universal Links (iOS) and App Links (Android)
interception so the OAuth flow stays entirely in the mobile browser.

**No action is required from API consumers** — the mobile-safe redirect is applied
automatically when the user accesses the `access_url` on a mobile device.

---

### Endpoint: Validate JWT

```
/api/uploadposts/users/validate-jwt
```

(Optional) Allows you to validate a JWT token. The primary validation occurs automatically when the user accesses the `access_url`.

*   **Method:** `GET`
*   **Headers:**
    *   `Authorization: Bearer YOUR_JWT_TOKEN`
*   **Body Parameters:** None. The token is read from the `Authorization` header, not from the request body.
*   **Example Request (curl):**
    ```bash
    # Replace YOUR_JWT_TOKEN with the actual token string
    curl -X GET https://api.upload-post.com/api/uploadposts/users/validate-jwt \
      -H "Authorization: Bearer YOUR_JWT_TOKEN"
    ```
*   **Success Response (200 OK - Valid Token):** Returns the profile details associated with the token.
    ```json
    {
      "profile": {
        "social_accounts": {
          "tiktok": null,
          "instagram": "connected_account_details",
          // ... other platforms
        },
        "username": "your_platform_user_id_123",
        "ui_labels": {
          "connect.connectButton": "Bağlan",
          "connect.notConnected": "Bağlı değil"
        }
      },
      "success": true
    }
    ```
    *   `profile`: Contains details about the user profile linked to the token.
        *   `social_accounts`: An object showing the connection status for various platforms (e.g., `null` if not connected, or details if connected).
        *   `username`: The unique identifier provided when the profile was created.
        *   `ui_labels`: The connect-page text overrides currently stored for this profile, as set via [`generate-jwt`](#custom-ui-labels). Empty or absent when no overrides are stored.
    *   `success`: Indicates the token is valid.
*   **Success Response (200 OK - Invalid Token):**
    ```json
    {
      "isValid": false,
      "reason": "Token expired or invalid signature" // Example reason
    }
    ```
*   **Error Responses:**
    *   `401 Unauthorized`: Invalid, expired, or missing JWT token in the `Authorization` header.

---

## Facebook Pages

Retrieve Facebook page IDs associated with user profiles to enable posting to Facebook pages.

### Endpoint

```
/api/uploadposts/facebook/pages
```

---

### Get Facebook Pages

Fetches Facebook page IDs associated with a profile. You can use this endpoint to connect and start posting on Facebook pages.

*   **Method:** `GET`
*   **Headers:**
    *   `Authorization: Apikey YOUR_API_KEY`
*   **Query Parameters:**

    | Name    | Type   | Required | Description                                                                          |
    |---------|--------|----------|--------------------------------------------------------------------------------------|
    | profile | String | No       | The unique identifier of the profile. If not specified, returns all pages for your account. |

*   **Example Request (curl):**
    ```bash
    curl 'https://api.upload-post.com/api/uploadposts/facebook/pages?profile=your_profile' \
      -H 'Authorization: Apikey YOUR_API_KEY'
    ```
*   **Example Request (without profile parameter):**
    ```bash
    curl 'https://api.upload-post.com/api/uploadposts/facebook/pages' \
      -H 'Authorization: Apikey YOUR_API_KEY'
    ```
*   **Success Response (200 OK):**
    ```json
    {
      "pages": [
        {
          "page_id": "123456789",
          "page_name": "My Business Page",
          "profile": "your_platform_user_id_123"
        },
        {
          "page_id": "987654321", 
          "page_name": "Another Page",
          "profile": "your_platform_user_id_123"
        }
      ],
      "success": true
    }
    ```
    *   `pages`: Array of Facebook page objects associated with the profile(s).
        *   `page_id`: The Facebook page ID that can be used for posting.
        *   `page_name`: The display name of the Facebook page.
        *   `profile`: The profile identifier associated with this page.
    *   `success`: Indicates successful retrieval.
*   **Error Responses:**
    *   `401 Unauthorized`: Invalid or missing API Key.
    *   `404 Not Found`: No profile found with the provided identifier (if profile parameter is specified).

---

## Manual Credential Connections

Most platforms are connected through the [JWT connection page](#endpoint-generate-jwt-url) (OAuth). A few platforms instead use a **manual credential** model: you submit the credentials directly to a dedicated endpoint, Upload-Post validates them against the platform, encrypts the secret at rest, and links it to the profile. There is **no OAuth flow and no browser redirect**. Tokens/webhooks do not expire, so no reconnection is required unless you revoke them.

### Connect Discord

Links a Discord channel **incoming webhook** to a profile. Upload-Post validates the webhook by performing a `GET` on the webhook URL (expecting a `200` with the webhook `id`, `channel_id`, and `guild_id`), then encrypts and stores the webhook URL.

*   **Method:** `POST`
*   **Endpoint:** `/api/uploadposts/users/discord/credentials`
*   **Headers:**
    *   `Authorization: Apikey YOUR_API_KEY`
    *   `Content-Type: application/json`
*   **Body Parameters:**

    | Name             | Type   | Required | Description                                                                 |
    |------------------|--------|----------|-----------------------------------------------------------------------------|
    | profile_username | String | Yes      | The profile to link the Discord webhook to.                                 |
    | webhook_url      | String | Yes      | The Discord channel incoming webhook URL (`https://discord.com/api/webhooks/...`). |
    | name             | String | No       | Optional display name for the connection. Defaults to the webhook's name.   |

*   **Example Request (curl):**
    ```bash
    curl -X POST https://api.upload-post.com/api/uploadposts/users/discord/credentials \
      -H "Authorization: Apikey YOUR_API_KEY" \
      -H "Content-Type: application/json" \
      -d '{
        "profile_username": "your_platform_user_id_123",
        "webhook_url": "https://discord.com/api/webhooks/123456789/abcdef...",
        "name": "My Server #announcements"
      }'
    ```
*   **Success Response (200 OK):**
    ```json
    {
      "success": true,
      "message": "Discord credentials saved successfully"
    }
    ```
    On success, the profile's `social_accounts.discord` is set to the connection key (the supplied `name`, or the webhook id when `name` is omitted).
*   **Error Responses:**
    *   `400 Bad Request`: Missing `profile_username`/`webhook_url`, an invalid webhook URL format, or a webhook that fails validation (deleted/invalid).
    *   `401 Unauthorized`: Invalid or missing API Key.
    *   `404 Not Found`: User or profile not found.

To find the webhook URL: open Discord → **Server Settings → Integrations → Webhooks → New Webhook**, pick the target channel, and click **Copy Webhook URL**.

### Connect Telegram

Links a **Telegram bot** (bring-your-own bot) and a target chat/channel to a profile. Upload-Post validates the bot token via `getMe` and the chat via `getChat` (the bot must be an **admin** of the chat), then encrypts and stores the bot token.

*   **Method:** `POST`
*   **Endpoint:** `/api/uploadposts/users/telegram/credentials`
*   **Headers:**
    *   `Authorization: Apikey YOUR_API_KEY`
    *   `Content-Type: application/json`
*   **Body Parameters:**

    | Name             | Type   | Required | Description                                                                                  |
    |------------------|--------|----------|----------------------------------------------------------------------------------------------|
    | profile_username | String | Yes      | The profile to link the Telegram bot to.                                                     |
    | bot_token        | String | Yes      | The bot token from @BotFather (e.g. `123456:ABC-DEF...`).                                     |
    | chat_id          | String | Yes      | The target chat: a public channel `@username`, or a numeric chat id (e.g. `-100123456789`).  |
    | name             | String | No       | Optional display name for the connection. Defaults to the bot's username.                    |

*   **Example Request (curl):**
    ```bash
    curl -X POST https://api.upload-post.com/api/uploadposts/users/telegram/credentials \
      -H "Authorization: Apikey YOUR_API_KEY" \
      -H "Content-Type: application/json" \
      -d '{
        "profile_username": "your_platform_user_id_123",
        "bot_token": "123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11",
        "chat_id": "@my_channel",
        "name": "My Channel"
      }'
    ```
*   **Success Response (200 OK):**
    ```json
    {
      "success": true,
      "message": "Telegram credentials saved successfully"
    }
    ```
    On success, the profile's `social_accounts.telegram` is set to the connection key (the supplied `name`, or the bot username when `name` is omitted).
*   **Error Responses:**
    *   `400 Bad Request`: Missing `profile_username`/`bot_token`/`chat_id`, an invalid bot token, or a chat that is not reachable (often because the bot is not an admin of the chat).
    *   `401 Unauthorized`: Invalid or missing API Key.
    *   `404 Not Found`: User or profile not found.

To set up the bot: message [@BotFather](https://t.me/BotFather) → `/newbot` to get a `bot_token`, then add the bot to your target channel/group **as an administrator** so it can post.

---


---
# Video Format Requirements
URL: https://docs.upload-post.com/api/video-requirements

# Video Format Requirements

This document outlines the video format requirements for uploading to various social media platforms via the API.

## Automatic Video Transformation

Our API automatically transforms videos to adapt them to the specifications of each social network. However, if you use this feature, the upload will take longer because the transformation is performed first before uploading to the platforms. 

If you prefer faster uploads, you can pre-process your videos according to the specific requirements of each platform outlined in this document.

## Video Encoding Compatibility

Some video creation tools occasionally produce videos with encoding that Meta's systems don't accept. At times, their output needs to be re-encoded for compatibility.

### One Solution: Re-encode with FFmpeg

If your video uploads are failing, try re-encoding the video using FFmpeg, an open-source tool for video processing:

```bash
ffmpeg -i your_original_video.mp4 -c:v libx264 -preset medium -profile:v high -level 4.0 -pix_fmt yuv420p -c:a aac -movflags +faststart meta_compatible_video.mp4
```

This command converts your video to use the widely-compatible H.264 video codec and AAC audio codec, which Meta platforms accept.

Re-encoding "normalizes" your video to use standard encoding parameters that Meta's platforms are designed to process, without sacrificing quality. If you see these errors regularly, this simple step can save you frustration when sharing your creative content.

### FFmpeg Installation and Usage

**Installation instructions:**
- **macOS:** `brew install ffmpeg`
- **Windows:** `winget install ffmpeg`
- **Linux:** `sudo apt install ffmpeg` (Ubuntu/Debian) or `sudo dnf install ffmpeg` (Fedora)

**Parameters:**
- `-c:v libx264`: Uses H.264 video codec
- `-preset medium`: Balance between encoding speed and quality
- `-profile:v high -level 4.0`: Compatibility settings
- `-pix_fmt yuv420p`: Standard pixel format for maximum compatibility
- `-b:v 5000k`: Video bitrate (adjust as needed for quality)
- `-c:a aac`: AAC audio codec
- `-b:a 192k`: Audio bitrate
- `-movflags +faststart`: Optimizes file for web streaming

## TikTok Video Requirements

- **Supported Formats:** MP4 (recommended), WebM, MOV
- **Supported Codecs:** H.264 (recommended), H.265, VP8
- **Framerate:** Minimum: 23 FPS, Maximum: 60 FPS
- **Picture Size:** Minimum: 360 pixels (height and width), Maximum: 4096 pixels (height and width)
- **Duration:** Minimum: 3 seconds. Maximum via API: 10 minutes (600 seconds). Users may trim videos in the TikTok app.
- **Size:** Maximum: **4 GB** (Content Posting). Upload-Post rejects files over
  **3 GB** before rehost (`error_code=invalid_video_file`). TikTok's own Business
  documentation states 1 GB; that number is **false** and is not our cap.
- **Caption:** Up to 2,200 characters and 30 mentions
- **Custom cover image (`tiktok_cover_image_url`):** JPG, JPEG, WebP or PNG, max 20 MB
- **Rate limits:** 6 posts per minute and 15 posts per day per TikTok account

## Instagram Video Requirements

- **Container Format:** MOV or MP4 (MPEG-4 Part 14)
  - No edit lists
  - Moov atom at the head of the file
- **Audio Codec:** AAC
  - Maximum sampling rate: 48 kHz
  - 1 or 2 channels (mono or stereo)
  - Audio bitrate: 128 kbps
- **Video Codec:** HEVC or H264
  - Progressive scan
  - Closed GOP
  - Chroma subsampling: 4:2:0
  - Video bitrate: VBR, maximum 25 Mbps
- **Frame Rate:** 23-60 FPS
- **Image Size:**
  - Maximum horizontal pixels: 1,920
  - Aspect ratio: 0.01:1 to 10:1
  - Recommended aspect ratio: 9:16 (to avoid cropping or white space)
- **Duration & Size:**
  - Maximum duration: 15 minutes (Instagram increased from 90 seconds)
  - Minimum duration: 3 seconds
  - Maximum file size: 300 MB (feed/Reels). Stories video: 100 MB (`error_code=invalid_video_file`)

> **Note:** Instagram now supports Reels up to 15 minutes in duration. The previous 90-second limit has been removed.

## YouTube Video Requirements

- **File Size:** Maximum: 256 GB
- **Accepted MIME Types:** `video/*`, `application/octet-stream`

> **Important:** Custom thumbnails are not supported for YouTube Shorts; they only apply to standard YouTube videos.

## LinkedIn Video Requirements

- **File Size:** Minimum: 75 KB, Maximum: 500 MB
- **Duration:** Minimum: 3 seconds, Maximum: 30 minutes
- **Resolution:**
  - Range: 256 x 144 to 4,096 x 2,304
  - Aspect ratio: 1:2.4 to 2.4:1
- **Technical Specs:**
  - Frame rate: 10-60 fps
  - Bitrate: 192 kbps - 30 Mbps
- **Supported Formats:** AAC, ASF, FLV, MP3, MP4, MPEG-1, MPEG-4, MKV, WebM, H264/AVC, Vorbis, VP8, VP9, WMV2, WMV3

## Facebook Video Requirements

### Reels

- **File Format:** MP4 (recommended)
- **Resolution & Aspect Ratio:**
  - Recommended: 1080 x 1920 pixels
  - Minimum: 540 x 960 pixels
  - Aspect ratio: 9:16
- **Duration:**
  - 3-90 seconds
  - Maximum 60 seconds for page stories
- **Video Settings:**
  - Frame rate: 24-60 fps
  - Chroma subsampling: 4:2:0
  - Closed GOP (2-5 seconds)
  - Compression: H.264, H.265, VP9, AV1
  - Progressive scan
- **Audio Settings:**
  - Bitrate: 128 kbps+
  - Channels: Stereo
  - Codec: AAC (low complexity)
  - Sample rate: 48 kHz

### Normal Page Videos

Use `facebook_media_type=VIDEO` to upload regular videos to a Facebook Page. Normal page videos have more permissive requirements than Reels:

- **File Format:** MP4 (recommended), MOV, AVI, and other common formats
- **Resolution:** Up to 4096x4096 pixels, any aspect ratio
- **Duration:** Up to 4 hours
- **File Size:** Up to 10 GB
- **Thumbnails:** Custom thumbnails are supported via the `thumbnail_url` parameter. See the [Facebook Video Thumbnails API](https://developers.facebook.com/docs/graph-api/reference/video/thumbnails/#Creating).

## X (Twitter) Video Requirements

- **Recommended Codec & Profile:**
  - Video: H264 High Profile
  - Audio: AAC LC (Low Complexity)
- **Frame Rates:**
  - Recommended: 30 FPS, 60 FPS
  - Maximum: 60 FPS
- **Resolution:**
  - Recommended: 1280x720 (landscape), 720x1280 (portrait), 720x720 (square)
  - Dimensions: 32x32 to 1920x1920
- **Bitrate:**
  - Minimum Video: 5,000 kbps
  - Minimum Audio: 128 kbps
- **Aspect Ratio:**
  - Recommended: 16:9 (landscape/portrait), 1:1 (square)
  - Range: 1:3 to 3:1
  - Pixel Aspect Ratio: 1:1
- **Duration & File Size:**
  - Standard accounts: up to 20 minutes / 8 GB
  - Premium / Premium Plus: up to 125 minutes / 16 GB
- **Technical Video Specs:**
  - Pixel Format: YUV 4:2:0
  - GOP: Must not be open
  - Scan Type: Progressive scan
- **Technical Audio Specs:**
  - Channels: Mono or Stereo (not 5.1 or greater)
  - High-Efficiency AAC: Not supported
- **Custom Thumbnails:** Supported for Premium/Amplify users

## Threads Video Requirements

- **Container:** MOV or MP4
  - No edit lists
  - `moov` atom at the front
- **Audio Codec:** AAC
  - 48kHz sample rate maximum
  - 1 or 2 channels (mono/stereo)
  - Bitrate: 128 kbps
- **Video Codec:** HEVC or H264
  - Progressive scan
  - Closed GOP
  - 4:2:0 chroma subsampling
- **Frame Rate:** 23-60 FPS
- **Picture Size:**
  - Max columns (horizontal pixels): 1920
  - Aspect ratio: 0.01:1 to 10:1 (9:16 recommended)
- **Video Bitrate:** VBR, 100 Mbps maximum
- **Duration:**
  - Max: 300 seconds (5 minutes)
  - Min: > 0 seconds
- **File Size:** 1 GB maximum

## Pinterest Video Requirements

- **File Size:** Maximum: 2 GB
- **Supported Formats:** MP4, MOV, M4V
- **Duration:** Minimum: 4 seconds, Maximum: 15 minutes
- **Aspect Ratio:** Taller than 1.91:1 and shorter than 1:2. Recommended for standard video: 1:1 (square) or 2:3, 4:5 or 9:16 (vertical)

## Reddit Video Requirements

Reddit posting is currently unavailable. Uploads with `platform[]=reddit` return **HTTP 503** `error_code: "reddit_unavailable"`.

## Bluesky Video Requirements

- **File Size:** Maximum: 300 MB
- **Supported Formats:** MP4, MPEG, WebM, MOV
- **Duration:** Minimum: 1 second, Maximum: 10 minutes
- **Frame Rate:** 10-60 FPS
- **Resolution:** Minimum: 360x360 px, Maximum: 1920x1920 px
- **Aspect Ratio:** Automatically detected and passed as metadata
- **Daily video quota:** 25 videos / 10 GB per day

## Google Business Profile Video Requirements

- **Duration:** Maximum 30 seconds
- **File Size:** Maximum 28 MB
- Videos above ~12 MB are re-encoded. Above 28 MB / 30 s the upload is refused (`MEDIA_LIMITS`).

## Discord Video Requirements

- **Supported Formats:** MP4, MOV, WebM (any video type Discord accepts)
- **Caption:** Up to 2,000 characters, applied as the message content
- **Max File Size:** Bounded by the upload limit of the target Discord server. The video is sent as a single message attachment.

## Telegram Video Requirements

- **Supported Formats:** MP4, MOV (any video type Telegram accepts)
- **Caption:** Up to 1,024 characters
- **Delivery:** Sent via `sendVideo` by your connected bot. File size is bounded by the Telegram Bot API upload limit.

## Google Business Profile Video Requirements

- **Supported Formats:** MP4 (H.264 video, AAC audio)
- **Duration:** Up to 30 seconds. Longer clips are rejected with `error_code: MEDIA_LIMITS`.
- **File Size:** Google documents 75 MB, but its API refuses much smaller files with an opaque internal error. Videos above **12 MB are re-encoded automatically** (target ≈10 MB) before publishing, and the platform result carries a `warnings` entry when that happens. Anything still above **28 MB** after the re-encode is rejected with `error_code: MEDIA_LIMITS` and a message naming the size.
- **Resolution:** 720p or higher.
- **Tip:** Export at a moderate bitrate. A 20-second clip at 15 Mbps is about 39 MB, which Google will not accept; the same clip at 4 Mbps is about 10 MB and publishes immediately, without the re-encode step.
- **Why the low ceiling:** measured in production on 2026-09-06 — every hosted video of 30.9 MB or more was refused (38 of 38) and every one of 12.5 MB or less published (6 of 6), with identical container, codec and overlapping durations.


---
# Webhooks & Notifications
URL: https://docs.upload-post.com/api/webhooks

Upload-Post allows you to receive real-time notifications about upload statuses and social account connection changes. This eliminates the need to poll endpoints for status updates.

## Configuration

You can configure notifications in the **Upload-Post Dashboard**:

[**Configure Notifications**](https://app.upload-post.com/notifications)

You can choose to receive notifications via:
- **Webhook**: A POST request sent to your server with a JSON payload.
- **Telegram**: A message sent to a configured Telegram chat.

### Configuration via API

You can also configure your notification preferences programmatically using the API.

**Endpoint:** `POST https://app.upload-post.com/api/uploadposts/users/notifications`

**Authentication:** Requires a valid API Key.

**Request Body:**

```json
{
  "channels": {
    "webhook": true,
    "telegram": false
  },
  "webhook_url": "https://your-server.com/webhook-endpoint",
  "telegram_chat_id": "123456789",
  "webhook_events": {
    "upload_completed": true,
    "social_account_connected": true,
    "social_account_disconnected": true,
    "social_account_reauth_required": true
  }
}
```

**Response:**

```json
{
  "success": true,
  "notifications": {
    "channels": { "webhook": true, ... },
    "webhook_url": "...",
    "webhook_secret": "whsec_3f9a…",
    "webhook_events": { "upload_completed": true, "social_account_connected": true, ... },
    ...
  }
}
```

The same endpoint supports **`GET`** (read the current settings, including
`webhook_secret`) and **`DELETE`** (remove the account webhook: clears
`webhook_url`, `webhook_events` and `webhook_secret` and turns the `webhook`
channel off; Telegram/Slack/WhatsApp settings are untouched).

### Per-profile webhooks

Agencies and white-label integrators can route each profile's (tenant's)
events to a different URL. A profile webhook fires **in addition to** the
account webhook, has its own optional `webhook_events` filter, and is signed
with the same account `webhook_secret`. Leave the account-level `webhook`
channel off if you only want per-profile delivery.

| Method   | Endpoint                                  | Body / query |
| :------- | :---------------------------------------- | :----------- |
| `GET`    | `/api/uploadposts/users/profile-webhook`  | `?profile_username=acme` |
| `POST`   | `/api/uploadposts/users/profile-webhook`  | `{"profile_username": "acme", "webhook_url": "https://…", "webhook_events": {"upload_completed": true}}` — `webhook_events` is optional (omit = all events, `null` = reset) |
| `DELETE` | `/api/uploadposts/users/profile-webhook`  | `{"profile_username": "acme"}` |

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/profile-webhook \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"profile_username": "acme", "webhook_url": "https://acme.example.com/upload-post"}'
```

Response: `{"success": true, "profile_username": "acme", "webhook_url": "https://acme.example.com/upload-post", "webhook_events": null}`.
Authentication: account API key or dashboard session (the profile-scoped
connect JWT cannot change webhooks).

## Verifying signatures

Every webhook request (account and per-profile) carries these headers:

| Header                      | Value |
| :-------------------------- | :---- |
| `X-Upload-Post-Signature`   | `sha256=<hex HMAC-SHA256>` — present whenever the account has a `webhook_secret` |
| `X-Upload-Post-Timestamp`   | Unix time (seconds) when the request was signed |
| `X-Upload-Post-Event`       | Event name, e.g. `upload_completed` |
| `X-Upload-Post-Delivery`    | Unique id of this delivery — use it to de-duplicate |
| `User-Agent`                | `Upload-Post-Webhooks/1.0` |

The signature is `HMAC_SHA256(webhook_secret, "<timestamp>.<raw body>")`.
Verify it against the **raw request bytes** — do not parse and re-serialise
the JSON first — reject timestamps older than ~5 minutes to prevent replays,
and compare with a constant-time function.

The secret is created automatically the first time you save a `webhook_url`
(or a profile webhook). Read it with `GET /api/uploadposts/users/notifications`
or in the dashboard under **Notifications → Signing secret**. Rotate it with:

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/webhook-secret \
  -H "Authorization: Apikey YOUR_API_KEY"
# → {"success": true, "webhook_secret": "whsec_…"}
```

Rotation takes effect immediately: deploy the new secret on your receiver
first (accept both during the switch) to avoid rejected deliveries.
Deliveries made before a secret existed carry no signature header.

**Node.js (Express)**

```js

const app = express();
const SECRET = process.env.UPLOAD_POST_WEBHOOK_SECRET;

app.post("/upload-post", express.raw({ type: "application/json" }), (req, res) => {
  const ts = req.header("X-Upload-Post-Timestamp");
  const sig = (req.header("X-Upload-Post-Signature") || "").replace("sha256=", "");
  if (!ts || Math.abs(Date.now() / 1000 - Number(ts)) > 300) return res.sendStatus(400);
  const expected = crypto.createHmac("sha256", SECRET).update(`${ts}.`).update(req.body).digest("hex");
  const ok = sig.length === expected.length &&
    crypto.timingSafeEqual(Buffer.from(sig, "hex"), Buffer.from(expected, "hex"));
  if (!ok) return res.sendStatus(401);
  const event = JSON.parse(req.body);
  // handle event…
  res.sendStatus(200);
});
```

**Python (Flask)**

```python
import hmac, hashlib, time
from flask import Flask, request, abort

app = Flask(__name__)
SECRET = "whsec_…"

@app.post("/upload-post")
def upload_post_webhook():
    ts = request.headers.get("X-Upload-Post-Timestamp", "")
    sig = request.headers.get("X-Upload-Post-Signature", "").removeprefix("sha256=")
    if not ts or abs(time.time() - int(ts)) > 300:
        abort(400)
    expected = hmac.new(SECRET.encode(), f"{ts}.".encode() + request.get_data(), hashlib.sha256).hexdigest()
    if not hmac.compare_digest(sig, expected):
        abort(401)
    event = request.get_json()
    return "", 200
```

### Webhook Events

You can subscribe to specific event types using the `webhook_events` object. Set each event key to `true` to receive it, or `false` to disable it. If omitted, all events are enabled by default.

| Event | Description |
| :--- | :--- |
| `upload_completed` | Fired when an upload process completes (success or failure). |
| `social_account_connected` | Fired when a social account is connected or reconnected. |
| `social_account_disconnected` | Fired when a social account is disconnected (manually or automatically). |
| `social_account_reauth_required` | Fired when a social account requires re-authentication. |

## Webhook Payloads

### `upload_completed`

Sent when an upload process completes (whether successfully or with a failure).

```json
{
  "event": "upload_completed",
  "job_id": "a1b2c3d4e5f6...",
  "user_email": "user@example.com",
  "profile_username": "your_profile_username",
  "platform": "instagram",
  "media_type": "video",
  "title": "My Awesome Video",
  "caption": "Check this out! #cool",
  "result": {
    "success": true,
    "url": "https://www.instagram.com/p/C1234567890/",
    "publish_id": "17987654321098765",
    "post_id": "17987654321098765",
    "error": null
  },
  "created_at": "2024-03-15T14:30:00.000000"
}
```

#### Field Descriptions

| Field | Type | Description |
| :--- | :--- | :--- |
| `event` | `string` | The type of event: `upload_completed`. |
| `job_id` | `string` | The persistent job identifier returned when the post was created or scheduled. Use this to correlate webhook events with your original API requests. Only present when the upload was triggered via the API with a `job_id`. |
| `user_email` | `string` | The email address of the user who initiated the upload. |
| `profile_username` | `string` | The username of the profile associated with the upload. |
| `platform` | `string` | The social platform where the post was uploaded (e.g., `instagram`, `youtube`, `tiktok`). |
| `media_type` | `string` | The type of media uploaded (`video`, `photo`, or `text`). |
| `title` | `string` | The title provided for the post. |
| `caption` | `string` | The caption or description of the post. |
| `result` | `object` | An object containing the outcome of the upload attempt. |
| `result.success` | `boolean` | `true` if the upload was successful, `false` otherwise. |
| `result.url` | `string` | The direct URL to the published post (if available and successful). |
| `result.publish_id` | `string` | The ID assigned to the post by the platform. |
| `result.error` | `string` | A description of the error if the upload failed. |
| `created_at` | `string` | The timestamp of the event in ISO 8601 format. |

### `social_account_connected`

Sent when a social account is successfully connected or reconnected via OAuth.

```json
{
  "event": "social_account_connected",
  "user_email": "user@example.com",
  "platform": "instagram",
  "account_name": "my_instagram_handle",
  "status": "connected",
  "profile_username": "your_profile_username",
  "created_at": "2024-03-15T14:30:00.000000"
}
```

### `social_account_disconnected`

Sent when a social account is disconnected. This can happen due to:
- Manual disconnection by the user
- Automatic disconnection due to persistent authentication failures (account blocked)

```json
{
  "event": "social_account_disconnected",
  "user_email": "user@example.com",
  "platform": "tiktok",
  "account_name": "my_tiktok_handle",
  "status": "disconnected",
  "profile_username": "your_profile_username",
  "reason": "manual_disconnect",
  "created_at": "2024-03-15T14:30:00.000000"
}
```

### `social_account_reauth_required`

Sent when a social account's access token can no longer be refreshed and the user must re-authenticate. This typically happens when:
- The refresh token has expired
- The user revoked access on the platform
- Multiple consecutive token refresh attempts have failed

```json
{
  "event": "social_account_reauth_required",
  "user_email": "user@example.com",
  "platform": "youtube",
  "account_name": "UCxxxxxxxxx",
  "status": "reauth_required",
  "reason": "token_refresh_threshold_exceeded",
  "created_at": "2024-03-15T14:30:00.000000"
}
```

### Connection Status Event Fields

| Field | Type | Description |
| :--- | :--- | :--- |
| `event` | `string` | The event type: `social_account_connected`, `social_account_disconnected`, or `social_account_reauth_required`. |
| `user_email` | `string` | The email address of the account owner. |
| `platform` | `string` | The social platform (e.g., `instagram`, `youtube`, `tiktok`, `x`, `linkedin`, `facebook`, `threads`, `pinterest`, `reddit`, `bluesky`, `snapchat`, `google_business`, `discord`, `telegram`, `slack`, `mastodon`, `nostr`, `lemmy`, `devto`, `hashnode`, `wordpress`, `whop`, `listmonk`). |
| `account_name` | `string` | The account identifier on the platform (e.g., username, channel ID). |
| `status` | `string` | The new connection status: `connected`, `disconnected`, or `reauth_required`. |
| `profile_username` | `string` | The Upload-Post profile associated with this account (if applicable). |
| `reason` | `string` | Additional context for the status change (e.g., `manual_disconnect`, `account_blocked`, `token_refresh_threshold_exceeded`, `max_auth_strikes`). Only present for `disconnected` and `reauth_required` events. |
| `created_at` | `string` | The timestamp of the event in ISO 8601 format. |

## Usage Notes

- **Idempotency**: While we strive to deliver each notification exactly once, you should handle potential duplicate events based on your own unique identifiers if necessary (though `post_id` or `publish_id` can serve this purpose for successful posts).
- **Security**: Use HTTPS and [verify the signature](#verifying-signatures) of every delivery. Use `X-Upload-Post-Delivery` to de-duplicate retries.
- **Delivery pause after repeated failures**: each notification channel (webhook, Telegram, Slack, WhatsApp) has a circuit breaker. After 5 consecutive failed deliveries (timeouts, connection errors, non-2xx responses) the channel is paused for 30 minutes and then retried; one successful delivery resets the counter. Deliveries skipped while paused are not retried later, so keep your endpoint answering `2xx` within 10 seconds and process the payload asynchronously.
- **Event Filtering**: Use the `webhook_events` field in your notification settings to subscribe only to the events you need. If not specified, all events are enabled by default.
- **Replacing Polling**: If you were previously polling the profile endpoint to check connection status, subscribe to `social_account_connected`, `social_account_disconnected`, and `social_account_reauth_required` events instead.


---
# Set YouTube Thumbnail
URL: https://docs.upload-post.com/api/youtube-thumbnail

# Set YouTube Thumbnail

Set (or replace) the custom thumbnail of a video that is already on YouTube — for example a Short you published without one, or a video whose thumbnail you want to A/B test. The video must belong to the YouTube channel connected to the profile.

### Endpoint

```
POST /api/uploadposts/youtube/thumbnail
```

### Headers

| Name | Value | Description |
|------|-------|-------------|
| Authorization | Apikey your-api-key-here | Your API key |

### Parameters

Send `multipart/form-data` (for a file) or JSON (for a URL).

| Name | Type | Required | Description |
|------|------|----------|-------------|
| user | String | Yes | Profile name whose YouTube channel owns the video |
| video_id | String | Yes | YouTube video ID (the `post_id` returned by the upload, or the `v=` value of the video URL) |
| thumbnail | File | One of the two | Image file. JPG, PNG, GIF or BMP, max **2 MB**. YouTube recommends 1280×720 (16:9) for videos and 1080×1920 (9:16) for Shorts. |
| thumbnail_url | String | One of the two | Public URL of the image instead of a file |

### Example Request (file)

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/youtube/thumbnail \
  -H "Authorization: Apikey your-api-key-here" \
  -F "user=my_profile" \
  -F "video_id=dQw4w9WgXcQ" \
  -F "thumbnail=@/path/to/thumbnail.jpg"
```

### Example Request (URL)

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/youtube/thumbnail \
  -H "Authorization: Apikey your-api-key-here" \
  -H "Content-Type: application/json" \
  -d '{"user": "my_profile", "video_id": "dQw4w9WgXcQ", "thumbnail_url": "https://example.com/thumbnail.jpg"}'
```

### Success Response

```json
{
  "success": true,
  "video_id": "dQw4w9WgXcQ",
  "width": 1280,
  "height": 720,
  "thumbnails": {
    "default": { "url": "https://i.ytimg.com/vi/dQw4w9WgXcQ/default.jpg", "width": 120, "height": 90 },
    "high": { "url": "https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg", "width": 480, "height": 360 }
  }
}
```

### Error Responses

| Status | When |
|--------|------|
| 400 | Missing `user`/`video_id`, no thumbnail provided, not an image, unsupported format or over 2 MB |
| 401 | Invalid API key, or the YouTube token needs reconnecting |
| 403 | YouTube refused it — most often the channel is not verified (custom thumbnails require a [verified channel](https://support.google.com/youtube/answer/171664)) |
| 404 | The video does not exist or does not belong to the connected channel |
| 429 | YouTube API quota exhausted for today |

The `error` field carries YouTube's reason in plain language; `youtube_status` is the raw status YouTube returned.

### Notes

- Thumbnails can take a few minutes to refresh on YouTube's CDN.
- You can also send `thumbnail` / `thumbnail_url` directly on [Upload Video](./upload-video.md) to set it during the upload; this endpoint is for videos that are already published.

### Related Endpoints

- [Upload Video](./upload-video.md)
- [Unpublish Post](./unpublish-post.md)


---
# Labeling AI-Generated Content
URL: https://docs.upload-post.com/guides/ai-content-labeling

# Labeling AI-Generated Content

If you publish AI-generated or AI-edited media through Upload-Post, several platforms let you (and in some cases require you to) disclose that the content is AI-generated. Upload-Post exposes each platform's native disclosure flag, plus a single cross-platform alias so you can set one field and have it mapped everywhere it is supported.

## The `is_ai_generated` flag

Send `is_ai_generated=true` with any upload request (`/api/upload`, `/api/upload_photos`) and Upload-Post maps it to each target platform's native AI-disclosure field:

| Platform | Native field set | Effect |
|---|---|---|
| TikTok (video, Direct Post) | `is_aigc` | Video is tagged "Creator labeled as AI-generated" |
| Instagram (Reels, photos, carousels) | `is_ai_generated` | "AI info" label shown under the account name; for carousels the label applies to the whole post |
| YouTube | `containsSyntheticMedia` | Altered/synthetic content disclosure on the video |
| X (Twitter) | `made_with_ai` | Disclosure that the post contains AI-generated media (media posts only) |

The per-platform parameters (`is_aigc`, `containsSyntheticMedia`, `made_with_ai`, and Instagram's `is_ai_generated`) keep working exactly as before — if you send a platform-specific value it takes precedence over the alias, so you can, for example, label a post on TikTok but not on YouTube in the same request.

```bash
curl -X POST https://api.upload-post.com/api/upload \
  -H "Authorization: ApiKey YOUR_API_KEY" \
  -F 'user=YOUR_PROFILE' \
  -F 'platform[]=tiktok' -F 'platform[]=instagram' -F 'platform[]=youtube' \
  -F 'video=@video.mp4' \
  -F 'title=My video' \
  -F 'is_ai_generated=true'
```

The flag is preserved for scheduled posts and can be reviewed in the calendar editor before publication.

## Platforms without an API-level label

Some platforms have no public API parameter for AI disclosure; for those, `is_ai_generated` is a documented no-op and labeling happens through other channels:

- **Facebook Pages** — Reels accept `facebook_is_ai_generated`. Other Page posts: Meta applies labels via automatic detection of C2PA/IPTC metadata embedded in the media, or you disclose in-app.
- **LinkedIn** — labels applied automatically when images carry C2PA Content Credentials.
- **Pinterest** — applies its "Gen AI" label via IPTC metadata detection and its own classifiers.

If your generation tool supports **C2PA Content Credentials**, keep them embedded in your media files — TikTok, Meta, LinkedIn, and Pinterest detect and auto-label C2PA-tagged uploads (auto-applied labels generally cannot be removed).

## When you should label

Labeling accurately is your responsibility as the publisher:

- **Platform policies** require disclosure of realistic AI-generated or significantly synthetic media (TikTok, YouTube, and Meta may penalize unlabeled AI content their detectors catch).
- **EU AI Act (Regulation 2024/1689), Article 50(4)**: if you are subject to EU law and publish deep fakes, or AI-generated/manipulated text published to inform the public on matters of public interest, you must disclose that the content is artificially generated or manipulated. See our [Terms of Use](https://www.upload-post.com/terms-of-use/) §3.3.
- Clearly unrealistic/stylized AI content (e.g. obvious cartoons) generally does not require YouTube's `containsSyntheticMedia` disclosure — check each platform's current policy.

Upload-Post does not verify whether your content is AI-generated; the flag is a self-disclosure tool.


---
# AI Shorts Uploader
URL: https://docs.upload-post.com/guides/ai-shorts-uploader

# AI Shorts Uploader

The **AI Shorts Uploader** is a built-in assistant in the Upload-Post dashboard that watches your short-form video and writes the title, description and hashtags for each platform you publish to — **YouTube, Instagram and TikTok** — in seconds.

It is available from the **Shorts Uploader** screen in the app via the **Generate with AI** button.

## What it does

When you click **Generate with AI**, the platform:

1. Sends your video to a multimodal AI model (Gemini).
2. Analyses the visual content, pacing and on-screen text.
3. Returns optimized copy per platform:
   - **YouTube** — title (≤100 chars) + description (≤5,000 chars) with relevant hashtags.
   - **Instagram** — caption (≤2,200 chars) tuned for Reels.
   - **TikTok** — caption (≤150 chars) tuned for the For You feed.

You can also rewrite an already-generated set of captions into another language (Spanish, English, Japanese, …) without re-uploading the video. Each rewrite counts as one analysis.

## Monthly quotas per plan

Each successful analysis (and each language rewrite) consumes one unit from your monthly quota. The counter resets every 30 days from your last reset.

| Plan          | Analyses per month |
| :------------ | -----------------: |
| Free          |                 10 |
| Basic         |                100 |
| Professional  |                300 |
| Advanced      |                600 |
| Business      |              1,000 |

When you reach your quota, the dashboard shows an in-app message and the API responds with **HTTP 429**. Quotas reset automatically at the start of your next billing cycle.

## Video requirements

| Constraint   | Limit         |
| :----------- | :------------ |
| Max file size | 100 MB       |
| Max duration | 5 minutes     |
| Format       | `.mp4` recommended |

These limits exist because the feature is tuned for short-form vertical content. For longer videos, generate captions manually or use the [FFmpeg Video Editor API](../api/ffmpeg-editor.md) to trim a highlight first.

## Error responses

When the monthly quota is exhausted:

```json
{
  "success": false,
  "message": "You have used all 100 AI Shorts analyses for this month. Upgrade your plan for a higher quota or wait until your next billing period.",
  "remaining_analyses": 0
}
```

HTTP status: `429 Too Many Requests`.

If you also see a separate yellow banner reading *"Too many requests. Please wait a moment before trying again"*, that is the global per-minute API rate limit (see [Rate limits](./rate-limits.md)) — wait a few seconds and retry.

## FAQ

**Does retrying a failed analysis count twice?** No. The counter is incremented only after a successful analysis is returned to your browser.

**Can I see how many analyses I have left?** Every analysis response (dashboard and API) includes `remaining_analyses`. A standalone usage endpoint is on the roadmap.

**Is the feature available on the public API?** Yes — `POST /api/uploadposts/analyze-shorts` and `POST /api/uploadposts/rewrite-captions` accept your API key and share the same monthly quota. See the [AI Shorts API](../api/ai-shorts.md) reference.


---
# Airtable Integration
URL: https://docs.upload-post.com/guides/airtable-integration

# Airtable Integration

Manage your video uploads to social media platforms directly from Airtable.

## Set Up

Running Airtable Automation Scripts requires a paid Airtable plan that includes automations with scripts.

This guide shows you how to automatically upload videos to social media platforms from Airtable via Upload-Post.

## Gather Your API Key

Start by getting your API Key from your [Upload-Post account](https://app.upload-post.com/) in the "API Keys" section. This key will be used in the script.

Be sure you have configured your social media accounts in Upload-Post before proceeding.

:::info
**URL Support for Media Files**: You can now pass URLs for both photo and video uploads instead of binary files. Simply provide the direct URL to your media file in the `video` or `photos[]` parameter.
:::

## Create an Airtable Workspace

In Airtable, create a new workspace with these fields:

* `Title` as Single Line Text
* `Platforms` as Multi Select with types: tiktok, instagram
* `Video` as Attachment
* `User` as Single Line Text
* `Status` as Single Line Text

## Enter Test Video Data

Add sample data to test the integration:

* `Title`: Enter a title for your video
* `Platforms`: Select one or more platforms (tiktok, instagram)
* `Video`: Attach a video file (MP4 format recommended)
* `User`: Enter your username
* `Status`: Enter `pending` (lowercase)

## Build an Automation Script

Let's create an Airtable automation script that uploads videos via Upload-Post:

### Add Trigger

1. In your workspace, click on _Automation_ then _+New automation_
2. Name the automation
3. Click _Choose a Trigger_
4. Select _When a Record is Created_
5. Select your table
6. Click Done

### Add Action

1. Click _Add Action_
2. Select _Run Script_
3. Delete any default code in the script editor
4. Copy and paste this code:

```javascript
const API_KEY = "Your API Key"; // Get your key at app.upload-post.com

console.log(`Starting Upload ${base.name}!`);

const uploadVideo = async (data) => {
  const { title, platforms, videoUrl, user } = data;
  
  const formData = new FormData();
  
  if (title) formData.append('title', title);
  if (user) formData.append('user', user);
  
  // Add platforms
  if (platforms && platforms.length > 0) {
    platforms.forEach(platform => {
      formData.append('platform[]', platform);
    });
  }
  
  // Download and attach video
  if (videoUrl) {
    const videoResponse = await fetch(videoUrl);
    const videoBlob = await videoResponse.blob();
    formData.append('video', videoBlob);
  }

  console.log("Uploading video to platforms:", platforms);

  const response = await fetch("https://api.upload-post.com/api/upload", {
    method: "POST",
    body: formData,
    headers: {
      "Authorization": `Apikey ${API_KEY}`
    }
  }).then((res) => res.json());

  return response;
};

const table = base.getTable("Videos");
const query = await table.selectRecordsAsync();
const filteredRecords = query.records.filter((record) => {
  const status = record.getCellValue("Status");
  return status === "pending";
});

for (let record of filteredRecords) {
  const title = record.getCellValue("Title");
  const video = record.getCellValue("Video");
  const platforms = record.getCellValue("Platforms");
  const user = record.getCellValue("User");

  if (!video || video.length === 0) {
    console.log("No video found for record");
    continue;
  }

  const response = await uploadVideo({
    title,
    platforms: platforms.map((x) => x.name),
    videoUrl: video[0].url,
    user
  });

  console.log(response);

  if (response) {
    let status = response.status === "success" ? "success" : "error";
    
    await table.updateRecordAsync(record, {
      Status: status
    });
  }
}
```

Replace `Your API Key` with your actual Upload-Post API key.

## Test the Script

In the script editor, press _>Test_

The script will run and process any pending records. If successful, you'll see your videos being uploaded to the selected platforms, and the Status field will update to "success".

## Security Best Practices

- Never share your API key
- Consider using environment variables where possible
- Review records before processing large batches


---
# Avoid Timeouts with Asynchronous Uploads
URL: https://docs.upload-post.com/guides/async-uploads

# Avoid Timeouts with Asynchronous Uploads

Are your requests taking too long and resulting in timeouts? For video, photo, or text post uploads that may require more processing time (file processing, social network publishing queues, etc.), use the `async_upload` parameter to make your request asynchronously.

## How does it work?

- Send your request with `async_upload=true` to the appropriate upload endpoint.
- The API will immediately respond with a `request_id`.
- Use this `request_id` to check the progress and result at the status endpoint.

## Checking Status

The status endpoint supports two different identifier types:

1. **`request_id`**: Returned by upload endpoints when `async_upload=true`
2. **`job_id`**: Returned when you schedule posts with `scheduled_date`

### For Async Uploads

```bash
GET /api/uploadposts/status?request_id=<REQUEST_ID>
```

### For Scheduled Posts

After scheduling a post with `scheduled_date`, the API returns a `job_id`. Use it to check the status after the scheduled time:

```bash
GET /api/uploadposts/status?job_id=<JOB_ID>
```

## Relevant Endpoints

- [Text upload](../api/upload-text): `POST /api/upload_text`
- [Video upload](../api/upload-video): `POST /api/upload`
- [Photo upload](../api/upload-photo): `POST /api/upload_photos`
- [Upload status](../api/upload-status): `GET /api/uploadposts/status?request_id=<REQUEST_ID>` or `?job_id=<JOB_ID>`

## Quick Example: Asynchronous Video Upload


---
# Authentication
URL: https://docs.upload-post.com/guides/authentication

# Authentication

Upload-Post uses API keys to authenticate requests. This guide explains how to obtain and use your API key.

## Getting Your API Key

1. Log in to your [Upload-Post Dashboard](https://app.upload-post.com/)
2. Navigate to the "API Keys" section
3. Click "Generate New API Key"
4. Copy and securely store your API key

## Using Your API Key

Include your API key in the `Authorization` header of all API requests:

```bash
Authorization: Apikey your-api-key-here
```

### Example Request

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'video=@/path/to/your/video.mp4' \
  -F 'title="My Video"' \
  -F 'user="test"' \
  -F 'platform[]=tiktok' \
  -X POST https://api.upload-post.com/api/upload
```

## Security Best Practices

- **Never share your API key**: Keep your API key confidential
- **Use environment variables**: Store your API key in environment variables
- **Rotate keys regularly**: Generate new API keys periodically
- **Restrict access**: Only share API keys with trusted team members
- **Monitor usage**: Regularly check your API key usage in the dashboard

## API Key Limits

- Free tier includes 10 uploads per month
- Additional uploads available through paid plans

## Troubleshooting

If you receive a 401 Unauthorized error:
1. Verify your API key is correct
2. Check if your API key has expired
3. Ensure you're using the correct header format
4. Confirm your account is active

For additional help, contact our [support team](mailto:info@upload-post.com).


---
# Connecting Accounts
URL: https://docs.upload-post.com/guides/connecting-accounts

# Connecting Social Accounts

## Manual Credential Connections

Most platforms are connected through the JWT connection page (OAuth). A few platforms use a **manual credential** model instead: you submit the credentials to a dedicated endpoint, Upload-Post validates them with the platform, encrypts the secret at rest, and links it to the profile — **no OAuth and no browser redirect**. These credentials do not expire.

### Connecting Discord (manual credentials)

Discord is connected with a **channel incoming webhook URL**, not OAuth.

1. In Discord, open **Server Settings → Integrations → Webhooks → New Webhook**.
2. Choose the channel the posts should land in and click **Copy Webhook URL**.
3. Send the webhook URL to Upload-Post:

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/discord/credentials \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "profile_username": "your_user_id_123",
    "webhook_url": "https://discord.com/api/webhooks/123456789/abcdef...",
    "name": "My Server #announcements"
  }'
```

Upload-Post validates the webhook (GET on the URL), encrypts and stores it, and sets `social_accounts.discord` on the profile. After connecting, post with `platform[]=discord` on the [text](../api/upload-text.md), [photo](../api/upload-photo.md), and [video](../api/upload-video.md) endpoints. Discord supports text (max 2000 chars), up to 10 images per message, and video. Analytics are not available for Discord.

➡️ **See details:** [Connect Discord API Reference](../api/user-profiles.md#connect-discord)

### Connecting Telegram (manual credentials)

Telegram is connected with **your own bot** plus a target chat, not OAuth.

1. In Telegram, message [@BotFather](https://t.me/BotFather), send `/newbot`, and follow the prompts to obtain a **bot token**.
2. Add the bot to the target channel or group **as an administrator** (it must be an admin to post).
3. Determine the `chat_id`: a public channel can use its `@username`; private chats use the numeric id (e.g. `-100123456789`).
4. Send the credentials to Upload-Post:

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/telegram/credentials \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "profile_username": "your_user_id_123",
    "bot_token": "123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11",
    "chat_id": "@my_channel",
    "name": "My Channel"
  }'
```

Upload-Post validates the token (`getMe`) and chat (`getChat`), encrypts and stores the bot token, and sets `social_accounts.telegram` on the profile. After connecting, post with `platform[]=telegram` on the [text](../api/upload-text.md), [photo](../api/upload-photo.md), and [video](../api/upload-video.md) endpoints. Telegram supports text (max 4096 chars), single or multiple photos (album), and video (caption max 1024 chars). Analytics are not available for Telegram.

➡️ **See details:** [Connect Telegram API Reference](../api/user-profiles.md#connect-telegram)

### Connecting Slack (manual credentials)

Slack is connected with a channel **Incoming Webhook URL** (no OAuth). In Slack, create an app, enable *Incoming Webhooks*, and add one to the target channel to obtain a URL like `https://hooks.slack.com/services/T.../B.../xxxx`.

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/slack/credentials \
  -H "Authorization: Apikey YOUR_API_KEY" -H "Content-Type: application/json" \
  -d '{ "profile_username": "your_user_id_123", "webhook_url": "https://hooks.slack.com/services/T00/B00/xxxx", "name": "Team Channel" }'
```

After connecting, post with `platform[]=slack` on the [text](../api/upload-text.md) endpoint (text only; a Slack Incoming Webhook cannot upload media). Use `slack_title` to override the shared caption. No analytics.

### Connecting Mastodon (manual credentials)

Mastodon uses a per-instance **access token**. In your Mastodon instance go to *Preferences → Development → New application*, then copy the access token.

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/mastodon/credentials \
  -H "Authorization: Apikey YOUR_API_KEY" -H "Content-Type: application/json" \
  -d '{ "profile_username": "your_user_id_123", "instance_url": "https://mastodon.social", "access_token": "YOUR_TOKEN", "name": "@you@mastodon.social" }'
```

Supports `platform[]=mastodon` on text, photo (up to 4 images), and video endpoints. Use `mastodon_title` to override. No analytics.

### Connecting Nostr (manual credentials)

Nostr signs notes with **your private key** (nsec or hex) and broadcasts to relays. No server account is required.

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/nostr/credentials \
  -H "Authorization: Apikey YOUR_API_KEY" -H "Content-Type: application/json" \
  -d '{ "profile_username": "your_user_id_123", "private_key": "nsec1...", "relays": "wss://relay.damus.io,wss://nos.lol", "name": "My Nostr" }'
```

Text only (`platform[]=nostr`); `relays` is optional (sensible defaults are used). Use `nostr_title` to override. No analytics.

### Connecting Lemmy (manual credentials)

Lemmy uses your **instance URL, username, password**, and a target **community**. Posts require a community.

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/lemmy/credentials \
  -H "Authorization: Apikey YOUR_API_KEY" -H "Content-Type: application/json" \
  -d '{ "profile_username": "your_user_id_123", "instance_url": "https://lemmy.world", "username": "you", "password": "YOUR_PASSWORD", "community": "technology", "name": "Lemmy" }'
```

Supports `platform[]=lemmy` on the text and photo endpoints (video is not natively hosted). A title is required per post (`lemmy_title` or derived from the first line). No analytics.

### Connecting Dev.to (manual credentials)

Dev.to publishes Markdown articles with a single **API key** (Settings → Extensions → DEV API Keys).

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/devto/credentials \
  -H "Authorization: Apikey YOUR_API_KEY" -H "Content-Type: application/json" \
  -d '{ "profile_username": "your_user_id_123", "api_key": "YOUR_DEV_API_KEY", "name": "Dev.to" }'
```

Text/article only (`platform[]=devto`). `devto_title` sets the article title (or it is derived from the first line); the shared caption becomes the Markdown body. No analytics.

### Connecting Hashnode (manual credentials)

Hashnode uses a **Personal Access Token** and the target **publication ID**.

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/hashnode/credentials \
  -H "Authorization: Apikey YOUR_API_KEY" -H "Content-Type: application/json" \
  -d '{ "profile_username": "your_user_id_123", "pat": "YOUR_PAT", "publication_id": "PUBLICATION_ID", "name": "My Blog" }'
```

Text/article only (`platform[]=hashnode`). `hashnode_title` sets the post title; the caption becomes the Markdown content. No analytics.

### Connecting WordPress (manual credentials)

WordPress (self-hosted) uses your **site URL, username, and an Application Password** (Users → Profile → Application Passwords).

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/wordpress/credentials \
  -H "Authorization: Apikey YOUR_API_KEY" -H "Content-Type: application/json" \
  -d '{ "profile_username": "your_user_id_123", "site_url": "https://myblog.com", "username": "admin", "application_password": "xxxx xxxx xxxx xxxx", "name": "My Blog" }'
```

Supports `platform[]=wordpress` on text (posts), photo (featured image), and video endpoints. `wordpress_title` sets the post title; the caption becomes the post body. No analytics.

### Connecting Whop (manual credentials)

Whop posts to a community forum with an **API key** and the target **experience ID**.

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/whop/credentials \
  -H "Authorization: Apikey YOUR_API_KEY" -H "Content-Type: application/json" \
  -d '{ "profile_username": "your_user_id_123", "api_key": "YOUR_WHOP_API_KEY", "experience_id": "exp_XXXX", "name": "My Community" }'
```

Text only (`platform[]=whop`). Posting to a community forum requires an API key with the `forum:post:create` permission. Use `whop_title` to set the post title. No analytics.

### Connecting Listmonk (manual credentials)

Listmonk (self-hosted newsletter) uses your **instance URL, username, password**, and a target **list ID**. Each post creates and sends a campaign.

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/listmonk/credentials \
  -H "Authorization: Apikey YOUR_API_KEY" -H "Content-Type: application/json" \
  -d '{ "profile_username": "your_user_id_123", "instance_url": "https://list.myblog.com", "username": "admin", "password": "YOUR_PASSWORD", "list_id": "1", "name": "Newsletter" }'
```

Text only (`platform[]=listmonk`). `listmonk_title` sets the campaign subject; the caption becomes the email body. No analytics.


---
# API Error Handling: Upload Endpoints
URL: https://docs.upload-post.com/guides/error-handling

# API Error Handling: Upload Endpoints

This document explains the structure of responses you will receive from the Video Upload (`POST /api/upload`) and Photo Upload (`POST /api/upload_photos`) endpoints, including how success and errors are indicated.

## 1. Successful Request Processing (HTTP 200 OK)

When your upload request is successfully processed by our server (meaning your authentication was valid, input was generally okay, and usage limits weren't exceeded *before* starting), you will **always receive an HTTP `200 OK` status code**.

The JSON response body will look like this:

```json
{
  "success": true,
  "results": {
    "tiktok": {
      "success": true,
      "publish_id": "1234567890123456789",
      "status": "PUBLISH_SUCCESS"
    },
    "instagram": {
      "success": true,
      "container_id": "9876543210987654321"
    },
    "linkedin": {
       "success": false,
       "error": "Your LinkedIn session has expired. Please reconnect your LinkedIn account. Go to Manage Users, remove the account, and connect it again."
    }
    // ... other requested platforms
  },
  "usage": {
    "count": 5,
    "limit": 120,
    "last_reset": "2023-10-27T10:00:00.000Z"
  }
}
```

**Key Points:**

*   `"success": true` indicates the API server processed your request.
*   **`"results"`:** This dictionary is crucial. It contains the outcome for **each individual platform** you requested.
    *   **Platform Success:** If `results[platform].success` is `true`, the upload to that platform likely succeeded. Additional platform-specific IDs (like `publish_id`, `container_id`, `post_id`) may be included.
    *   **Platform Failure:** If `results[platform].success` is `false`, the upload to that specific platform failed. The `results[platform].error` field will contain a message explaining the reason (e.g., token expired, API error from the platform, file issue).
    *   **Important:** An error on one platform (like LinkedIn in the example) **does not stop** attempts on other platforms. Always check the `success` flag for each platform in the `results`.
*   `"usage"`: Provides information about your current API usage count and limit after this request.

## 2. Request Failure (Non-200 HTTP Status Codes)

If there's a fundamental problem with your request *before* we attempt to upload to individual platforms, you will receive an HTTP status code other than `200 OK`. The response body will typically look like this:

```json
{
  "success": false,
  "message": "A description of the error"
}
```

Here are common error status codes and their meanings:

*   **`400 Bad Request`**
    *   **Meaning:** Your request was malformed or missing required information.
    *   **Common Causes:** Missing `video` or `photos` file, missing `title`, invalid `platform` name, missing `user` identifier in the form data when required.
    *   **`message` examples:** `"Video file and title are required"`, `"Title cannot be empty"`, `"Username required"`, `"Invalid platforms: [platform_name]"`, `"Username not associated with any profile"`.

*   **`401 Unauthorized`**
    *   **Meaning:** Authentication failed.
    *   **Common Causes:** Missing `Authorization` header, using an invalid or expired API Key or Bearer Token.
    *   **`message` examples:** `"Authorization header required"`, `"Invalid or expired token"`, `"Invalid API key"`, `"API key expired"`.

*   **`404 Not Found`**
    *   **Meaning:** The user associated with your authentication could not be found in our system.
    *   **`message` example:** `"User not found"`.

*   **`429 Too Many Requests`**
    *   **Meaning:** You have exceeded an API usage limit.
    *   **Common Causes:** Reaching your monthly upload limit, or (for Professional plan users) reaching the daily upload limit for a specific social media account.
    *   **`message` examples:** `"You have reached your monthly limit of X uploads"`, `"You have reached the daily limit of 5 uploads for: Instagram (account_name)"`.

*   **`500 Internal Server Error`**
    *   **Meaning:** An unexpected error occurred on our server while processing your request.
    *   **`message` example:** Usually contains technical details about the error. If you encounter this repeatedly, please contact support.

**In summary:** Always check the HTTP status code first. If it's `200 OK`, examine the `success` flag within the `results` dictionary for each platform. If it's not `200 OK`, check the `message` field in the response body for the reason.


---
# Limit of uploads
URL: https://docs.upload-post.com/guides/limit-of-uploads

# Limit of uploads

### Social Hard Caps Per Network

To protect your connected accounts and stay compliant with each social network, Upload-Post enforces platform hard caps using a rolling 24-hour window. When a cap is reached for a specific account on a given network, further posts to that account/network are rejected until the window rolls over.

- What counts toward the cap: only successful publishes recorded for that account/network in the last 24 hours.
- Scope: **Per connected social account**. These limits are NOT global to your Upload-Post user.
  - **Example**: If you manage **5 Profiles**, and each Profile has its own TikTok account connected, you get the full limit for *each* TikTok account. (e.g., 5 TikTok accounts × 15 posts = **75 posts per day** total).
- Scheduled posts: caps are re-checked at execution time; if the cap is already reached, the publish will be rejected then.

Recommended and enforced daily caps

| Social Network     | Hard Cap (posts per 24h) |
| :----------------- | -----------------------: |
| Instagram          |                       50 |
| TikTok             |                       15 |
| LinkedIn           |                      150 |
| YouTube            |                       10 |
| Facebook           |                       25 |
| X (Twitter)        |              See per-plan table below |
| Threads            |                       50 |
| Pinterest          |                       20 |
| Reddit             | unavailable (`reddit_unavailable`) |
| Bluesky            | 25 videos / 10 GB (video quota) |

#### X (Twitter) per-plan daily caps

X uploads have a per-plan hard cap (one cap per connected X profile, per 24-hour rolling window) instead of a single flat limit. All other platforms keep the flat caps shown above.

| Plan         | X Hard Cap (posts per profile per 24h) |
| :----------- | -------------------------------------: |
| Default (free) |                                   10 |
| Basic        |                                     10 |
| Professional |                                     20 |
| Advanced     |                                     30 |
| Business     |                                     30 |

Error response when the cap is reached

- Status: 429 Too Many Requests
- Body example:
```json
{
  "success": false,
  "message": "Post verification failed",
  "violations": [
    {
      "platform": "instagram",
      "type": "hard_cap",
      "message": "Daily cap reached for instagram: 50/50 in last 24h",
      "used_last_24h": 50,
      "cap": 50
    }
  ]
}
```

What else the verifier checks

- Duplicate/similar content within 48h (per account/network) to reduce spam risk and shadow bans.
- Mention limits to avoid spammy behavior (e.g., excessive mentions or repeating the same handle too frequently).
- Media and content sanity checks evolve over time to align with network guidelines.


---
# Make Integration
URL: https://docs.upload-post.com/guides/make-integration

# Make Integration

Upload-Post provides seamless integration with Make (formerly Integromat) for automated video publishing workflows. This guide walks you through connecting your Upload-Post account with Make in 3 simple steps.

## Getting Started with Upload-Post

1. Create an account or log in to your existing [Upload-Post account](https://app.upload-post.com/)
2. Navigate to the "API Keys" section
3. Generate an API key for your Make integration

## API Configuration

For Make integration, you'll need to configure an HTTP module with the following parameters:

```bash
Endpoint: https://api.upload-post.com/api/upload
Method: POST
Headers: Authorization: Apikey your-api-key-here
```

**Note:** Find your API key in your [Upload-Post Manage API Keys](https://app.upload-post.com/) section.

:::info
**URL Support for Media Files**: You can now pass URLs for both photo and video uploads instead of binary files. Simply provide the direct URL to your media file in the `video` or `photos[]` parameter.
:::

## Form Data Configuration & Make.com Setup

Configure your Make HTTP module with these parameters:

| Field | Value | Required |
| ----- | ----- | -------- |
| title | Your video title | Optional |
| user | Your username | Required |
| platform[] | tiktok | Required |
| video | Binary file | Required |

### Make.com Configuration Steps:

1. Add an **HTTP Module**: In your Make.com scenario, add an HTTP module and choose the "Make a Request" action.
2. Configure the Request Settings:
   - **Method**: Set to POST.
   - **URL**: Enter `https://api.upload-post.com/api/upload`.
   - **Headers**: Add a header with:
     - **Key**: Authorization
     - **Value**: `Apikey [YOUR_API_KEY]`
   - Set the **Request Body**: Change the body type to `multipart/form-data` and add the following form fields:
     - **title**: Set the value to your desired title (you can use a variable if needed).
     - **user**: Enter your username you set in Upload-Post.
     - **platform[]**: Set the value to `tiktok`.
     - **video**: Attach the binary file (your video file). Make sure this field is mapped to the binary data you want to send.
3. Save and Test: Save your scenario and run a test to ensure that the video upload works correctly via the API.

## Advanced Configuration Options

### For Instagram Uploads

To upload to Instagram instead, simply change the platform value to `instagram` in your form data.

### Uploading to Multiple Platforms

To upload to both TikTok and Instagram simultaneously, add both platform values by creating multiple fields with the same name `platform[]` in the Make.com HTTP module.

### Securely Storing API Keys in Make.com

For better security, avoid hardcoding your API key directly in scenarios:

- Create an **App Key** in Make.com
- Store your Upload-Post API key as a constant
- Reference the constant in your HTTP module headers
- When sharing scenarios, use scenario blueprints which do not expose your keys

Example of referencing an API key constant in Make:

```json
"headers": {
  "Authorization": "Apikey {{constants.uploadPostApiKey}}"
}
```

Need more guidance? Check out this detailed forum post: [Make.com Community Tutorial](https://community.make.com/t/how-to-upload-video-to-tiktok-and-instagram/69264)

## Need Assistance?

For additional help with your Make integration, contact our [support team](mailto:info@upload-post.com).


---
# MCP Server (ChatGPT / Claude / Cursor)
URL: https://docs.upload-post.com/guides/mcp-server-integration

# MCP Server (ChatGPT / Claude / Cursor)

Upload-Post ships an official **Model Context Protocol (MCP)** server. Connect it to ChatGPT, claude.ai, Claude Desktop, Claude Code, Cursor, or any other MCP-compatible AI agent and your assistant can publish, schedule, analyze and manage social media on your behalf — without writing any code.

The server exposes **50 tools** across the public Upload-Post API, OAuth connectors, media staging, and the hosted Upload Studio.

- **Hosted endpoint:** `https://mcp.upload-post.com/mcp`
- **Source:** [github.com/Upload-Post/upload-post-mcp](https://github.com/Upload-Post/upload-post-mcp) (MIT)

## How authentication works

The server supports **two auth modes**, and chooses automatically based on what the client sends:

### 1. API key (Claude Desktop, Claude Code, Cursor, any MCP client with `mcp.json`)

Each MCP client sends its own Upload-Post API key in the `Authorization` header of every request. The server uses that key for the duration of the session and stores nothing.

```
Authorization: ApiKey YOUR_UPLOAD_POST_API_KEY
```

`Authorization: Bearer YOUR_UPLOAD_POST_API_KEY` is also accepted, for clients that only allow Bearer tokens.

### 2. OAuth 2.1 (claude.ai Custom Connectors, ChatGPT, any "Add via URL" client)

Clients that don't accept custom headers can connect via the standard OAuth flow — no API key copying required. The server implements:

- **RFC 9728** Protected Resource Metadata at `/.well-known/oauth-protected-resource`
- **RFC 8414** Authorization Server Metadata at `/.well-known/oauth-authorization-server`
- **RFC 7591** Dynamic Client Registration at `/register`
- **RFC 6749 §4.1 + PKCE (RFC 7636)** authorization-code flow at `/authorize` + `/token`
- **RFC 7009** Token revocation at `/revoke`

The user is sent to `app.upload-post.com/oauth/authorize` to log in and approve. The issued access token (prefix `up_oauth_`) is opaque — the server resolves it to the user's API key on every request via an internal introspection call.

You can view and revoke active OAuth connectors at any time from **Connected Apps** in the dashboard.

## Get your API key

1. Sign in to [app.upload-post.com](https://app.upload-post.com/).
2. Open the **API Keys** section.
3. Generate a new key and copy it.

## Connect claude.ai (Custom Connector)

Go to **Settings → Connectors → Add custom connector** in claude.ai and paste:

```
https://mcp.upload-post.com/mcp
```

claude.ai will:

1. Discover the OAuth metadata at `/.well-known/oauth-protected-resource`.
2. Dynamically register itself via `/register` (no manual client ID needed).
3. Open `app.upload-post.com/oauth/authorize` in a popup so you can log in and approve.
4. Exchange the returned authorization code for an access token, and store it.

That's it — 50 tools become available inside claude.ai with zero local configuration. Revoke any time from **Connected Apps**.

## Connect ChatGPT

Upload-Post is a reviewed app in the **ChatGPT app directory**, so the fastest path is to add it from ChatGPT itself:

1. Open the [Upload-Post app in ChatGPT](https://chatgpt.com/plugins/plugin_asdk_app_6a1fed02ff5c8191b4060c5807221f26) — or search for *Upload-Post* under **Apps**.
2. Click **Connect** and sign in with your Upload-Post account (OAuth).

No Developer mode and no URL to paste. After approval, ChatGPT can call the Upload-Post tools and open the Upload Studio component for browser-based media uploads.

<details>
<summary>Add it manually as a custom MCP connector</summary>

You can still wire the server up by hand — useful for workspaces where the directory listing is not available:

1. In ChatGPT on the web, enable **Developer mode** under **Settings → Apps → Advanced settings** (Plus, Pro, Business, Enterprise or Edu).
2. In **Apps**, click **Create app** and paste the hosted endpoint:

```
https://mcp.upload-post.com/mcp
```

3. Set **Authentication** to **OAuth** and authorize.

</details>

Either way ChatGPT uses the same OAuth flow as claude.ai.

:::note Upload Studio is ChatGPT-only
The Studio widget is built on the ChatGPT Apps SDK, so the server only advertises `open_upload_studio` to ChatGPT. In claude.ai, Claude Desktop, Claude Code, Cursor and other clients the tool is hidden. To publish a file from your computer there, give the assistant a public HTTPS URL of the file, or upload it from the [dashboard](https://app.upload-post.com). Clients that can run HTTP requests themselves (Claude Code, Cursor, scripts) can also stage the file with `create_media_upload` → PUT → `complete_media_upload` and pass the returned `media_url` to `upload_video`.
:::

## Connect Claude Desktop / Claude Code / Cursor

Add the following entry to your MCP config (`~/.claude/mcp.json`, `~/.cursor/mcp.json`, or your IDE's equivalent):

```json
{
  "mcpServers": {
    "upload-post": {
      "url": "https://mcp.upload-post.com/mcp",
      "headers": {
        "Authorization": "ApiKey YOUR_UPLOAD_POST_API_KEY"
      }
    }
  }
}
```

Restart your client. You should see 50 `upload-post` tools become available.

## What the agent can do

| Group         | Tools |
|---------------|-------|
| Upload        | `upload_video`, `upload_photos`, `upload_text`, `upload_document` |
| Status        | `get_status`, `get_job_status`, `get_history`, `get_media` |
| Schedule      | `list_scheduled`, `cancel_scheduled`, `edit_scheduled` |
| Analytics     | `get_analytics`, `get_total_impressions`, `get_post_analytics`, `get_platform_metrics` |
| Users         | `get_account_info`, `list_users`, `create_user`, `delete_user`, `generate_jwt`, `validate_jwt` |
| Pages / boards| `get_facebook_pages`, `get_linkedin_pages`, `get_pinterest_boards`, `get_google_business_locations`, `select_google_business_location`, `get_reddit_detailed_posts` |
| Comments      | `get_post_comments`, `reply_to_comment`, `public_reply_to_comment` |
| DMs           | `send_dm`, `list_dm_conversations`, `manage_autodms` |
| FFmpeg        | `submit_ffmpeg_job`, `get_ffmpeg_job`, `download_ffmpeg_result`, `get_ffmpeg_consumption` |
| Queue         | `get_queue_settings`, `update_queue_settings`, `preview_queue` |
| Media staging | `create_media_upload`, `complete_media_upload`, `get_media_upload`, `delete_media_upload` |
| Studio        | `open_upload_studio` (ChatGPT only) |

Async uploads return a `request_id`; the agent polls `get_status` until `success: true`.

## Upload tool parameters

The four upload tools accept the same options as the REST API, but in **camelCase** (the MCP is built on the official [`upload-post` npm SDK](https://www.npmjs.com/package/upload-post), which maps them to the snake_case form fields of [`POST /api/upload`](../api/upload-video.md), [`POST /api/upload_photos`](../api/upload-photo.md) and [`POST /api/upload_text`](../api/upload-text.md)).

### Common parameters (all upload tools)

| Parameter | Type | Description |
|-----------|------|-------------|
| `user` | string | **Required.** Profile name (Upload-Post user). |
| `platforms` | string[] | **Required.** Array of platform identifiers, e.g. `["youtube", "tiktok"]`. |
| `title` | string | Caption / title. Required for YouTube and text posts. Reddit posting is currently unavailable (`reddit_unavailable`). |
| `description` | string | Description (platforms that support it). |
| `firstComment` | string | Comment posted right after publishing. |
| `scheduledDate` | string | ISO 8601 date for scheduled publishing, e.g. `2026-12-25T10:00:00Z`. Omit to post now. |
| `timezone` | string | IANA timezone for `scheduledDate`, e.g. `Europe/Madrid`. |
| `addToQueue` | boolean | Insert into the profile's [posting queue](../api/queue-system.md) instead of publishing now. |
| `asyncUpload` | boolean | Return immediately with a `request_id` (default `true`). |

Tool-specific media inputs:

- **`upload_video`** — `videoPathOrUrl` (public/signed HTTPS URL, or absolute local path for self-hosted MCP) **or** `videoBase64` (+ optional `videoFilename`). Hosted clients cannot read local paths or chat attachments: ChatGPT uses `open_upload_studio`; claude.ai needs a public URL or the dashboard; clients with a shell can use media staging (`create_media_upload` → PUT → `complete_media_upload`).
- **`upload_photos`** — `photosPathsOrUrls` (array of URLs or local paths), optional `altText`.
- **`upload_text`** — `title` is the post text; optional `linkUrl` for a link-preview card (LinkedIn, Bluesky, Facebook).
- **`upload_document`** — `documentPathOrUrl` (PDF/PPT/PPTX/DOC/DOCX, LinkedIn only), `linkedinVisibility`, `targetLinkedinPageId`.

### `platformOptions` — per-platform overrides

`upload_video`, `upload_photos` and `upload_text` accept a flat `platformOptions` object with camelCase keys. Each key maps 1:1 to a snake_case parameter of the REST API (`youtubeThumbnailUrl` → `thumbnail_url`, `tiktokPrivacyLevel` → `privacy_level`, …); the supported set is the option list of the [`upload-post` SDK](https://www.npmjs.com/package/upload-post). The most used keys:

**YouTube** (video)

| Key | Description |
|-----|-------------|
| `youtubeThumbnailUrl` | Custom thumbnail image URL. |
| `youtubeTags` | Video tags — string or array of strings. |
| `youtubeCategoryId` | Category ID (e.g. `"22"` for People & Blogs). |
| `youtubePrivacyStatus` | `public`, `unlisted` or `private`. |
| `youtubePlaylistId` | One playlist ID, an array, or a comma-separated list to add the video to. |
| `youtubeEmbeddable` | Allow embedding on other sites. |
| `youtubeLicense` | `youtube` or `creativeCommon`. |
| `youtubePublicStatsViewable` | Show public view stats. |
| `youtubeSelfDeclaredMadeForKids` | COPPA made-for-kids flag. |
| `youtubeContainsSyntheticMedia` | AI/synthetic content disclosure. |
| `youtubeDefaultLanguage` / `youtubeDefaultAudioLanguage` | BCP-47 language of title/description and audio. |
| `youtubeAllowedCountries` / `youtubeBlockedCountries` | Comma-separated country codes. |
| `youtubeHasPaidProductPlacement` | Paid product placement flag. |
| `youtubeRecordingDate` | Recording date (ISO 8601). |
| `youtubeSubtitles` | Array of `{ language, name?, url? }` subtitle tracks (SRT, VTT, SBV, SUB, ASS, SSA, TTML). |

**TikTok**

| Key | Description |
|-----|-------------|
| `tiktokPrivacyLevel` | `PUBLIC_TO_EVERYONE`, `MUTUAL_FOLLOW_FRIENDS`, `FOLLOWER_OF_CREATOR`, `SELF_ONLY`. |
| `tiktokDisableDuet` / `tiktokDisableComment` / `tiktokDisableStitch` | Interaction toggles. |
| `tiktokCoverTimestamp` | Cover frame timestamp in ms (video). |
| `tiktokPhotoCoverIndex` | Cover photo index, 0-based (photos). |
| `tiktokAutoAddMusic` | Auto add music (photos). |
| `tiktokIsAigc` | AI-generated content flag. |
| `tiktokPostMode` | `DIRECT_POST` or `MEDIA_UPLOAD`. `MEDIA_UPLOAD` is draft mode; same idea as `tiktokUploadToDraft: true`. |
| `tiktokUploadToDraft` | Alias of `tiktokPostMode: "MEDIA_UPLOAD"`. |
| `brandContentToggle` / `brandOrganicToggle` | Branded content disclosures. |

**Instagram**

| Key | Description |
|-----|-------------|
| `instagramMediaType` | Video: `REELS` or `STORIES`. Photos: `IMAGE` or `STORIES`. |
| `instagramCoverUrl` | Custom cover image URL (Reels). |
| `instagramThumbOffset` | Frame offset for the auto thumbnail. |
| `instagramShareToFeed` | Also show the Reel in the feed. |
| `instagramCollaborators` | Comma-separated collaborator usernames. |
| `instagramUserTags` | Comma-separated user tags. |
| `instagramLocationId` | Location ID. |
| `instagramAudioName` | Audio track name. |
| `instagramAltText` | Alt text per image (max 1000 chars). |

**Facebook**

| Key | Description |
|-----|-------------|
| `facebookPageId` | Page ID to publish to (see `get_facebook_pages`). |
| `facebookMediaType` | `REELS`, `STORIES` or `VIDEO`. |
| `facebookVideoState` | `PUBLISHED` or `DRAFT`. |
| `thumbnailUrl` | Thumbnail URL for normal page videos (`facebookMediaType: "VIDEO"`). |
| `facebookLinkUrl` | Link preview URL (text posts). |
| `facebookAltText` | Alt text per photo. |

**LinkedIn**

| Key | Description |
|-----|-------------|
| `linkedinPageId` / `targetLinkedinPageId` | Organization page ID (see `get_linkedin_pages`). |
| `linkedinVisibility` | `PUBLIC`, `CONNECTIONS`, `LOGGED_IN`, `CONTAINER`. |
| `linkedinLinkUrl` | Link preview URL (text posts). |
| `linkedinAltText` | Alt text per image (defaults to title). |

**Pinterest**

| Key | Description |
|-----|-------------|
| `pinterestBoardId` | Board ID (see `get_pinterest_boards`). |
| `pinterestLink` | Destination link for the pin. |
| `pinterestCoverImageUrl` | Cover image URL (video pins). |
| `pinterestCoverImageKeyFrameTime` | Key frame time in ms for the cover. |
| `pinterestAltText` | Alt text (photo pins). |

**X (Twitter)**

| Key | Description |
|-----|-------------|
| `xReplySettings` | Who can reply: `everyone`, `following`, `mentionedUsers`, `subscribers`, `verified`. |
| `xTaggedUserIds` | User IDs to tag in media. |
| `xLongTextAsPost` | Post long text as a single post instead of a thread. |
| `xThreadImageLayout` | Images per thread post, e.g. `"4,4"`. |
| `xQuoteTweetId` | Tweet ID to quote (text posts). |
| `xPollOptions` / `xPollDuration` | Poll with 2–4 options and duration in minutes (text posts). |
| `xCommunityId` | Community ID. |
| `xAltText` | Alt text per image (max 1000 chars). |
| `xPaidPartnership` | Paid partnership flag. |

**Threads / Reddit / Bluesky / Google Business**

| Key | Description |
|-----|-------------|
| `threadsLongTextAsPost` | Single post instead of a thread. |
| `threadsThreadMediaLayout` | Media items per Threads post, e.g. `"5,5"`. |
| `redditSubreddit` | Unused while Reddit posting is unavailable (503 `reddit_unavailable`). |
| `redditFlairId` | Unused while Reddit posting is unavailable. |
| `blueskyLinkUrl` | External embed link preview. |
| `blueskyAltText` | Alt text (string, JSON array, or `\|\|`-separated). |
| `threadsAltText` | Alt text per image (max 1000 chars). |
| `threadsReplyControl` | Who can reply. |
| `googleBusinessLocationId` | Location to publish to (see `get_google_business_locations`). |

Per-platform text overrides are also supported: `youtubeTitle`, `tiktokTitle`, `instagramTitle`, …, `youtubeDescription`, `linkedinDescription`, …, and `youtubeFirstComment`, `instagramFirstComment`, etc. take priority over the generic `title` / `description` / `firstComment` for that platform.

### Example: video to YouTube with thumbnail, tags and playlist

```json
{
  "tool": "upload_video",
  "arguments": {
    "user": "marketing",
    "platforms": ["youtube"],
    "title": "Spring launch 🌱",
    "description": "Everything new this season.",
    "videoPathOrUrl": "https://example.com/launch.mp4",
    "platformOptions": {
      "youtubePrivacyStatus": "public",
      "youtubeThumbnailUrl": "https://example.com/thumb.jpg",
      "youtubeTags": ["launch", "product"],
      "youtubeCategoryId": "22",
      "youtubePlaylistId": "PLxxxxxxxxxxxx"
    }
  }
}
```

For the complete parameter semantics, validation rules and platform requirements, see the REST references: [Upload Video](../api/upload-video.md), [Upload Photos](../api/upload-photo.md), [Upload Text](../api/upload-text.md) and [Upload Document](../api/upload-document.md).

## Browser media staging for ChatGPT and Claude

Hosted AI clients do not reliably hand local file paths to MCP tools. Upload-Post solves this with short-lived R2 media staging, so the browser uploads the binary directly while the MCP only orchestrates the flow.

1. The client calls `create_media_upload` with `filename`, `content_type`, `content_length`, `media_type`, and `source`.
2. The backend returns an `upload_id` and a presigned R2 `upload_url`.
3. The browser uploads the file directly to R2 with `PUT`.
4. The client calls `complete_media_upload` to validate the object and receive a temporary signed `media_url`.
5. The agent passes that `media_url` to `upload_video`, `upload_photos`, or another upload tool.

The staging object is not the durable source of truth. The normal Upload-Post upload/scheduler flow downloads the temporary URL and stores the media in the existing durable storage path before publishing or scheduling. This keeps the existing `/api/upload` behavior unchanged.

Staged objects expire after 24 hours whether they were used or not. Clients can also call `delete_media_upload` to remove an unused object earlier.

### Media staging limits

These limits are separate from normal social upload quotas and protect only the temporary browser-to-R2 ingest path.

| Plan | Uploads / month | Max file size | Monthly ingest | Pending uploads |
|------|-----------------|---------------|----------------|-----------------|
| Free / default | 10 | 250 MB | 2 GB | 3 |
| Basic / Premium | 100 | 500 MB | 25 GB | 10 |
| Professional / Pro | 500 | 1 GB | 100 GB | 50 |
| Advanced | 2,000 | 2 GB | 500 GB | 200 |
| Business | 10,000 | 5 GB | 2 TB | 1,000 |

## Production deployment notes

Deploy the backend before the MCP server. The MCP calls the new media staging endpoints, so older backend versions will not support browser uploads.

Backend environment variables are optional because production-safe defaults are built in:

```env
UPLOADPOST_MCP_MEDIA_STAGING_PREFIX=mcp-ingest/tmp
UPLOADPOST_MCP_MEDIA_TTL_HOURS=24
UPLOADPOST_MCP_MEDIA_PUT_EXPIRES_SECONDS=900
UPLOADPOST_MCP_MEDIA_GET_EXPIRES_SECONDS=21600
```

Set `UPLOAD_POST_R2_CONNECT_DOMAIN` on the MCP only if the public host used by the presigned R2 URLs is different from the default:

```env
UPLOAD_POST_R2_CONNECT_DOMAIN=https://0de16d5f5e344fe4757ecd62640a9ea3.r2.cloudflarestorage.com
```

R2 bucket CORS is required only for browser direct uploads. It does not affect API-to-API calls, scheduler workers, or the existing upload flow. Allow:

- Origins: `https://chatgpt.com`, `https://chat.openai.com`, `https://app.upload-post.com`, `https://mcp.upload-post.com`
- Methods: `PUT`, `GET`, `HEAD`
- Headers: at least `Content-Type`; use `*` if the Cloudflare dashboard requires a broad value for presigned S3 requests

Do not add a 24-hour lifecycle rule to the whole bucket. Cleanup is handled by the backend cron and targets only objects under `mcp-ingest/tmp`.

### Troubleshooting

- `redirect_uri not on allow-list`: the Upload-Post OAuth backend must allow the client redirect URI from ChatGPT or claude.ai before `/oauth/authorize` can complete.
- Browser `CORS` error during `PUT`: check the R2 CORS policy and make sure the MCP `UPLOAD_POST_R2_CONNECT_DOMAIN` matches the host returned in `upload_url`.
- `Video file not found` from an agent: use media staging instead of sending local filesystem paths to `upload_video`.

## Example prompts

Once connected, try these in your AI client:

- *"List the users in my Upload-Post account."*
- *"Publish this video URL to TikTok and Instagram under the profile `marketing` with the caption 'Spring launch'."*
- *"Schedule a text post on LinkedIn for next Monday at 10:00 Madrid time."*
- *"Show me the analytics for my profile `marketing` over the last month."*
- *"Reply privately to the latest comment on my Instagram post."*

The model decides which tools to call based on the request; you don't have to name them.

## Self-hosting (optional)

If you prefer to run the server yourself — for an isolated network, custom auth proxy, or stricter compliance requirements — the source repository ships with a multi-stage `Dockerfile` and a one-click Coolify configuration. See the project [README](https://github.com/Upload-Post/upload-post-mcp#deploy-on-coolify-docker) for instructions.

## Local-only stdio mode

For single-user setups (no hosted server), you can run the MCP locally via `npx`. Once published to npm, the configuration becomes:

```json
{
  "mcpServers": {
    "upload-post": {
      "command": "npx",
      "args": ["-y", "@upload-post/mcp"],
      "env": { "UPLOAD_POST_API_KEY": "YOUR_UPLOAD_POST_API_KEY" }
    }
  }
}
```

Both modes expose the same 50 tools.

## Need assistance?

Open an issue at [github.com/Upload-Post/upload-post-mcp/issues](https://github.com/Upload-Post/upload-post-mcp/issues) or contact our [support team](mailto:info@upload-post.com).


---
# n8n Integration
URL: https://docs.upload-post.com/guides/n8n-integration

# n8n Integration

Upload-Post provides seamless integration with n8n for automated video publishing workflows. This guide walks you through connecting your Upload-Post account with n8n.

## Getting Started with Upload-Post

1. Create an account or log in to your existing [Upload-Post account](https://app.upload-post.com/)
2. Navigate to the "API Keys" section
3. Generate an API key for your n8n integration

## API Configuration

For n8n integration, you'll need to configure an HTTP Request node with the following parameters:

```bash
Endpoint: https://api.upload-post.com/api/upload
Method: POST
Headers: Authorization: Apikey your-api-key-here
```

:::info
**URL Support for Media Files**: You can now pass URLs for both photo and video uploads instead of binary files. Simply provide the direct URL to your media file in the `video` or `photos[]` parameter.
:::

## n8n Workflow Configuration

Configure your n8n HTTP Request node with these parameters:

| Field | Value | Required |
| ----- | ----- | -------- |
| title | Your video title | Optional |
| user | Your username | Required |
| platform[] | tiktok | Required |
| video | Binary file | Required |

### Node Configuration Steps:

1. Add an **HTTP Request Node** to your workflow
2. Configure the node settings:
   - **Method**: POST
   - **URL**: `https://api.upload-post.com/api/upload`
   - **Headers**: `Authorization: Apikey [YOUR_API_KEY]`
   - **Body**: Set to `multipart/form-data` and add the required fields

## Complete JSON Node Configuration

Below is the complete JSON configuration for the HTTP Request node in n8n:

```json
{
  "parameters": {
    "method": "POST",
    "url": "https://api.upload-post.com/api/upload",
    "authentication": "none",
    "sendHeaders": true,
    "headerParameters": {
      "parameters": [
        {
          "name": "Authorization",
          "value": "Apikey YOUR_API_KEY_HERE"
        }
      ]
    },
    "sendBody": true,
    "bodyParameters": {
      "parameters": [
        {
          "name": "user",
          "value": "YOUR_USERNAME"
        },
        {
          "name": "title",
          "value": "= $input.item.title ? $input.item.title : 'My awesome video'"
        },
        {
          "name": "platform[]",
          "value": "tiktok"
        }
      ]
    },
    "options": {
      "redirect": {
        "redirect": true
      },
      "proxy": {
        "proxy": false
      },
      "timeout": 10000
    },
    "sendQuery": false,
    "contentType": "multipart-form-data",
    "queryParameterArrays": "indices",
    "bodyContentType": "multipart-form-data",
    "bodyParameterArrays": "indices",
    "formBinaryData": {
      "video": "={{$binary.data}}"
    }
  },
  "name": "Upload Video to TikTok",
  "type": "n8n-nodes-base.httpRequest",
  "typeVersion": 4,
  "position": [
    860,
    300
  ],
  "id": "98a25a44-b7fb-41e3-8b8b-3d33c5c6ea65"
}
```

### For Instagram Uploads

To upload to Instagram instead, change the platform value:

```json
"bodyParameters": {
  "parameters": [
    {
      "name": "user",
      "value": "YOUR_USERNAME"
    },
    {
      "name": "title",
      "value": "= $input.item.title ? $input.item.title : 'My awesome video'"
    },
    {
      "name": "platform[]",
      "value": "instagram"
    }
  ]
}
```

### Uploading to Multiple Platforms

To upload to both TikTok and Instagram simultaneously:

```json
"bodyParameters": {
  "parameters": [
    {
      "name": "user",
      "value": "YOUR_USERNAME"
    },
    {
      "name": "title",
      "value": "= $input.item.title ? $input.item.title : 'My awesome video'"
    },
    {
      "name": "platform[]",
      "value": "tiktok"
    },
    {
      "name": "platform[]",
      "value": "instagram"
    }
  ]
}
```

## Security Best Practices

- **Never hardcode your API key** directly in the workflow
- Create a **Credentials** entry in n8n for your Upload-Post API key
- Reference the credential in your HTTP Request node
- For workflows that will be shared, export without credentials
- Consider using environment variables or n8n's credential store

## Example Workflow: AI-powered Social Media Publisher

This workflow automates video publishing with AI-generated descriptions:

1. **Google Drive Trigger**: Monitors a folder for new videos
2. **OpenAI Transcription**: Extracts audio and converts to text
3. **OpenAI Description Generator**: Creates engaging descriptions
4. **Upload-Post HTTP Request**: Uploads to multiple platforms
5. **Error Handling**: Sends notifications on completion/errors

This workflow is available as a template: [View template on n8n.io](https://n8n.io/workflows/2894-upload-to-instagram-tiktok-and-youtube-from-google-drive/)

## Need Assistance?

For additional help with your n8n integration, contact our [support team](mailto:info@upload-post.com).


---
# How to Post to Instagram with an API
URL: https://docs.upload-post.com/guides/post-to-instagram-api

# How do I post to Instagram with an API, without my own Meta app?

Send a `multipart/form-data` request to Upload-Post's `POST https://api.upload-post.com/api/upload` (videos/Reels) or `POST /api/upload_photos` (photos and carousels) with `platform[]=instagram`. Upload-Post runs its own approved Meta app with the required Instagram permissions, so you skip the whole Meta developer flow: no app registration, no App Review, no permission approvals, no token refresh logic. You connect the Instagram account once via OAuth in the dashboard (Instagram Login). The account must be a **Business or Creator** account; it does **not** need to be linked to a Facebook Page.

## Steps

1. Create an account at [upload-post.com](https://www.upload-post.com) and generate an API key under **API Keys** ([Authentication](./authentication.md)).
2. Connect the Instagram account at [Manage Users](https://app.upload-post.com/manage-users), approving **all** requested permissions. To let *your* users connect their own Instagram accounts inside your product, generate a [white-label JWT connect link](./user-profile-integration.md) instead.
3. Post a Reel:

<Tabs groupId="lang">
<TabItem value="curl" label="cURL">

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'video=@/path/to/your/video.mp4' \
  -F 'title="Your Reel caption"' \
  -F 'user="test"' \
  -F 'platform[]=instagram' \
  -X POST https://api.upload-post.com/api/upload
```

</TabItem>
<TabItem value="python" label="Python">

```python
import requests

response = requests.post(
    "https://api.upload-post.com/api/upload",
    headers={"Authorization": "Apikey your-api-key-here"},
    files={"video": open("/path/to/your/video.mp4", "rb")},
    data={
        "title": "Your Reel caption",
        "user": "test",
        "platform[]": "instagram",
    },
)
print(response.json())
```

</TabItem>
<TabItem value="js" label="JavaScript">

```javascript

const form = new FormData();
form.append("video", new Blob([fs.readFileSync("/path/to/your/video.mp4")]), "video.mp4");
form.append("title", "Your Reel caption");
form.append("user", "test");
form.append("platform[]", "instagram");

const response = await fetch("https://api.upload-post.com/api/upload", {
  method: "POST",
  headers: { Authorization: "Apikey your-api-key-here" },
  body: form,
});
console.log(await response.json());
```

</TabItem>
</Tabs>

## Photos and carousels

Use [`POST /api/upload_photos`](../api/upload-photo.md) with a `photos[]` array (up to 10 items; Instagram also supports **mixed** photo + video carousels):

```bash
curl -X POST https://api.upload-post.com/api/upload_photos \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'photos[]=@image1.jpg' \
  -F 'photos[]=@image2.jpg' \
  -F 'photos[]=@video.mp4' \
  -F 'title="My mixed carousel"' \
  -F 'user="test"' \
  -F 'platform[]=instagram'
```

## Useful Instagram parameters

Full list in the [Upload Video reference](../api/upload-video.md#instagram):

| Parameter | What it does | Default |
|-----------|--------------|---------|
| `instagram_title` | Instagram-specific caption (falls back to `title`) | `title` |
| `media_type` | `REELS` or `STORIES` | `REELS` |
| `share_to_feed` | Also show the Reel in the feed | `true` |
| `share_mode` | Trial Reels: `CUSTOM`, `TRIAL_REELS_SHARE_TO_FOLLOWERS_IF_LIKED`, `TRIAL_REELS_DONT_SHARE_TO_FOLLOWERS` | `CUSTOM` |
| `cover_url` / `cover_image` | Custom Reel cover (URL or binary JPEG ≤ 8 MB) | none |
| `user_tags` | Users to tag, e.g. `"@user1, user2"` (video posts) | none |
| `collaborators` | Comma-separated collaborator usernames | none |
| `location_id` | Instagram location ID | none |
| `first_comment` | Auto-post a first comment after publishing | none |
| `scheduled_date` | ISO-8601 date to [schedule the post](../api/schedule-posts.md) | none |

Stories: send `media_type="STORIES"` on either endpoint (video or photo).

## Limits and gotchas

- **Account type:** personal Instagram accounts aren't supported by the Instagram API. Switch to Business or Creator. A Facebook Page is **not** required with Instagram Login. Error 400 on connect usually means an unverified or personal account ([FAQ](../resources/faq.md)).
- **Daily cap:** 50 Instagram posts per connected account per rolling 24 h ([upload limits](./limit-of-uploads.md)).
- **Video specs:** max 300 MB. See [Video Requirements](../api/video-requirements.md) and [Photo Requirements](../api/photo-requirements.md).
- **Photo tagging** uses JSON `user_tags` with x/y coordinates; see [Upload Photo](../api/upload-photo.md).


---
# How to Post to LinkedIn with an API
URL: https://docs.upload-post.com/guides/post-to-linkedin-api

# How do I post to LinkedIn with an API?

Send a request to Upload-Post with `platform[]=linkedin`: `POST https://api.upload-post.com/api/upload` for video, `POST /api/upload_photos` for images, `POST /api/upload_text` for text-only posts and `POST /api/upload_document` for native PDF/PPT/DOC document posts. Upload-Post uses its own approved LinkedIn app, so you skip the LinkedIn developer-program application and the Marketing API access request entirely. Connect the LinkedIn account once via OAuth, then post to the member profile or to any company page they administer.

## Steps

1. Create an account at [upload-post.com](https://www.upload-post.com) and generate an API key under **API Keys** ([Authentication](./authentication.md)).
2. Connect LinkedIn at [Manage Users](https://app.upload-post.com/manage-users) (or via a [white-label JWT link](./user-profile-integration.md) for your users).
3. Post a video:

<Tabs groupId="lang">
<TabItem value="curl" label="cURL">

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'video=@/path/to/your/video.mp4' \
  -F 'title="Your Video Title"' \
  -F 'description="Post commentary shown above the video"' \
  -F 'user="test"' \
  -F 'platform[]=linkedin' \
  -X POST https://api.upload-post.com/api/upload
```

</TabItem>
<TabItem value="python" label="Python">

```python
import requests

response = requests.post(
    "https://api.upload-post.com/api/upload",
    headers={"Authorization": "Apikey your-api-key-here"},
    files={"video": open("/path/to/your/video.mp4", "rb")},
    data={
        "title": "Your Video Title",
        "description": "Post commentary shown above the video",
        "user": "test",
        "platform[]": "linkedin",
    },
)
print(response.json())
```

</TabItem>
<TabItem value="js" label="JavaScript">

```javascript

const form = new FormData();
form.append("video", new Blob([fs.readFileSync("/path/to/your/video.mp4")]), "video.mp4");
form.append("title", "Your Video Title");
form.append("description", "Post commentary shown above the video");
form.append("user", "test");
form.append("platform[]", "linkedin");

const response = await fetch("https://api.upload-post.com/api/upload", {
  method: "POST",
  headers: { Authorization: "Apikey your-api-key-here" },
  body: form,
});
console.log(await response.json());
```

</TabItem>
</Tabs>

## Post as a company page

By default posts go to the member's personal profile. To post as an organization, pass `target_linkedin_page_id` with the page ID from [Get LinkedIn Pages](../api/get-linkedin-pages.md):

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=linkedin' \
  -F 'title="Our company is launching a new product!"' \
  -F 'target_linkedin_page_id="your_linkedin_page_id_here"' \
  -X POST https://api.upload-post.com/api/upload_text
```

## Text posts and documents

- **Text-only** posts (optionally with a link preview via `linkedin_link_url`): [Upload Text](../api/upload-text.md).
- **Documents** (PDF, PPT/PPTX, DOC/DOCX rendered as a native LinkedIn carousel viewer): [Upload Document](../api/upload-document.md), aka `POST /api/upload_document`.

## Useful LinkedIn parameters

Full list in the [Upload Video reference](../api/upload-video.md#linkedin):

| Parameter | What it does | Default |
|-----------|--------------|---------|
| `linkedin_title` | LinkedIn-specific title (falls back to `title`) | `title` |
| `linkedin_description` / `description` | Sent as the LinkedIn commentary | `title` |
| `visibility` | `PUBLIC`, `CONNECTIONS`, `LOGGED_IN`, `CONTAINER` | `PUBLIC` |
| `target_linkedin_page_id` | Post as an organization page | none |
| `first_comment` / `linkedin_first_comment` | Auto-post a first comment | none |
| `scheduled_date` | ISO-8601 date to [schedule the post](../api/schedule-posts.md) | none |

## Limits and gotchas

- **Daily cap:** 150 LinkedIn posts per connected account per rolling 24 h ([upload limits](./limit-of-uploads.md)).
- **Video specs:** up to 5 GB / 10 min. See [Video Requirements](../api/video-requirements.md).
- **Token expiry:** LinkedIn tokens last ~60 days and auto-refresh with regular use ([FAQ](../resources/faq.md)).


---
# How to Post to TikTok with an API
URL: https://docs.upload-post.com/guides/post-to-tiktok-api

# How do I post to TikTok with an API?

Send a `multipart/form-data` request to Upload-Post's `POST https://api.upload-post.com/api/upload` endpoint with your video file (or a public video URL), a `user` profile and `platform[]=tiktok`. Upload-Post runs its own approved TikTok app, so there's no developer application to register, no TikTok audit to pass, and no OAuth tokens to manage. You connect the TikTok account once in the dashboard and that's it. One caveat: TikTok posting needs a paid plan, it's not available on Free.

## Steps

1. Create an account at [upload-post.com](https://www.upload-post.com) and generate an API key in the [dashboard](https://app.upload-post.com/) under **API Keys** (see [Authentication](./authentication.md)).
2. Connect a TikTok account to a profile at [Manage Users](https://app.upload-post.com/manage-users). It's the normal TikTok login, no developer setup. For posting on behalf of *your* users, use [white-label JWT connect links](./user-profile-integration.md).
3. Call the [upload endpoint](../api/upload-video.md):

<Tabs groupId="lang">
<TabItem value="curl" label="cURL">

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'video=@/path/to/your/video.mp4' \
  -F 'title="Your Video Title"' \
  -F 'user="test"' \
  -F 'platform[]=tiktok' \
  -X POST https://api.upload-post.com/api/upload
```

</TabItem>
<TabItem value="python" label="Python">

```python
import requests

response = requests.post(
    "https://api.upload-post.com/api/upload",
    headers={"Authorization": "Apikey your-api-key-here"},
    files={"video": open("/path/to/your/video.mp4", "rb")},
    data={
        "title": "Your Video Title",
        "user": "test",
        "platform[]": "tiktok",
    },
)
print(response.json())
```

</TabItem>
<TabItem value="js" label="JavaScript">

```javascript

const form = new FormData();
form.append("video", new Blob([fs.readFileSync("/path/to/your/video.mp4")]), "video.mp4");
form.append("title", "Your Video Title");
form.append("user", "test");
form.append("platform[]", "tiktok");

const response = await fetch("https://api.upload-post.com/api/upload", {
  method: "POST",
  headers: { Authorization: "Apikey your-api-key-here" },
  body: form,
});
console.log(await response.json());
```

</TabItem>
</Tabs>

`video` also accepts a public URL instead of a file: `-F 'video="https://example.com/videos/myvideo.mp4"'`.

## Useful TikTok parameters

All parameters are documented in the [Upload Video reference](../api/upload-video.md#tiktok). The most used ones:

| Parameter | What it does | Default |
|-----------|--------------|---------|
| `tiktok_title` | TikTok-specific caption (falls back to `title`). Max 2,200 chars for video. | `title` |
| `post_mode` | `DIRECT_POST` publishes immediately; `MEDIA_UPLOAD` sends the video to TikTok drafts. Same idea as `tiktok_upload_to_draft=true` — either field, any TikTok account. | `DIRECT_POST` |
| `privacy_level` | `PUBLIC_TO_EVERYONE`, `MUTUAL_FOLLOW_FRIENDS`, `FOLLOWER_OF_CREATOR`, `SELF_ONLY` | `PUBLIC_TO_EVERYONE` |
| `disable_comment` / `disable_duet` / `disable_stitch` | Turn off comments, duets or stitches | `false` |
| `cover_timestamp` | Video frame (ms) to use as cover | `1000` |
| `is_aigc` | Declare AI-generated content | `false` |
| `scheduled_date` | ISO-8601 date to [schedule the post](../api/schedule-posts.md) | none |
| `first_comment` / `tiktok_first_comment` | Auto-post a first comment under the published post (video and photos). Needs a reconnected account — see [After publishing](#after-publishing-comments) | none |
| `async_upload` | Return immediately with a `request_id` and process in background ([recommended](./async-uploads.md)) | `false` |

:::tip Draft mode
Prefer `post_mode=MEDIA_UPLOAD`. `tiktok_upload_to_draft=true` is an alias of the same mode. Use either field on any TikTok account; do not pick a different one for Business vs standard. In draft mode TikTok ignores title/privacy metadata sent via API for video.
:::

## TikTok photo slideshows

Post images (with optional automatic music) through the [Upload Photos endpoint](../api/upload-photo.md):

```bash
curl -X POST https://api.upload-post.com/api/upload_photos \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'photos[]=@photo1.jpg' \
  -F 'photos[]=@photo2.jpg' \
  -F 'title="Photo slideshow with music"' \
  -F 'user="test"' \
  -F 'platform[]=tiktok' \
  -F 'auto_add_music=true'
```

## What your account can do: `capabilities`

Not every TikTok connection accepts the same optional fields. `GET /api/uploadposts/users` returns a
[`capabilities`](../api/user-profiles.md#account-capabilities) array on the TikTok account of each profile —
`music`, `location`, `cover_image`, `draft`, `profile_analytics`, `comments`, `trend_search` and friends.
Read it and offer only what the connection actually supports, instead of sending a field and hoping.

Two of them, **`comments` and `trend_search`, only appear once the account has been reconnected**: TikTok
grants those permissions at the moment of connecting, so an account linked a while ago never received them.
That account keeps publishing exactly as before — nothing about its uploads changes — but the comment and
keyword-search endpoints answer `400` with `error_code: "tiktok_reconnect_required"` until its owner
reconnects it from [Manage Users](https://app.upload-post.com/manage-users) (or through a
[white-label connect link](./user-profile-integration.md), if your users never see our dashboard).

Sending an unsupported field is never fatal: the post is published and the response carries a `warnings`
array naming what was ignored.

## After publishing: comments {#after-publishing-comments}

A TikTok post is not the end of the job — the comments under it are where the reach is decided.

**A first comment with the post.** Send `first_comment` (or `tiktok_first_comment` to override it just for
TikTok) on the upload and it is posted under the video or photo carousel as soon as it goes live. It never
fails the publish: by the time it is attempted the post is already up, so anything that goes wrong is a
`warnings` entry with `success: true`. It is skipped, with a warning, for a post sent to drafts — there is
nothing published to comment on yet.

**Reading and answering them.** The [Comments API](../api/comments.md) takes `platform=tiktok` on all three
operations, with `post_id` = the video id the upload returned:

```bash
# List the comments on a video
curl 'https://api.upload-post.com/api/uploadposts/comments?platform=tiktok&user=test&post_id=7401234567890123456' \
  -H 'Authorization: Apikey your-api-key-here'

# Reply to one of them (TikTok needs post_id even when replying)
curl -X POST https://api.upload-post.com/api/uploadposts/comments/create \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{"platform":"tiktok","user":"test","post_id":"7401234567890123456","comment_id":"7401234567890999888","message":"Thanks! Guide in the bio."}'
```

**Replies and moderation.** Both stay inside the same [Comments API](../api/comments.md). The replies under
a comment are the listing narrowed to a parent — add `comment_id` to
[`GET /comments`](../api/comments.md#list-comments) — and the moderation verbs are one endpoint,
[`POST /comments/action`](../api/comments.md#hide-like-or-pin-a-comment), with `action` set to `hide`,
`unhide`, `like`, `unlike`, `pin` or `unpin`. Each verb carries its own inverse, so replaying a request can
never flip a comment back.

```bash
# The replies under one comment
curl 'https://api.upload-post.com/api/uploadposts/comments?platform=tiktok&user=test&post_id=7401234567890123456&comment_id=7401234567890999888' \
  -H 'Authorization: Apikey your-api-key-here'

# Pin it to the top of the video
curl -X POST https://api.upload-post.com/api/uploadposts/comments/action \
  -H 'Authorization: Apikey your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{"platform":"tiktok","user":"test","comment_id":"7401234567890999888","action":"pin","post_id":"7401234567890123456"}'
```

A comment you just wrote takes about **10 seconds** to show up in a listing — TikTok indexes it
asynchronously, so an empty list right after creating one is not a failure.

All of this needs the `comments` capability, i.e. a reconnected account.

## Before publishing: what to post about

One endpoint, [`GET /suggestions`](../api/suggestions.md), turns "write something about instant cameras"
into a caption aimed at demand that exists:

- `type=keywords` — the terms people actually type on TikTok around your seed word, with their search
  volume. Needs `trend_search`, i.e. a reconnected account.
- `type=hashtags` — the tags to pair with that keyword and how many views each one carries, optionally
  ranked per country (`country_code`) and language. Needs `profile_analytics`, which any recent
  connection has.

## After publishing: how it did

[Get Analytics](../api/get-analytics.md) gives you the numbers, and on TikTok it gives you a lot more than
four counters. Everything below needs `profile_analytics`, which any recent connection has:

- [`GET /post-analytics`](../api/get-analytics.md#tiktok-the-full-per-post-breakdown) — per post, on top of
  views, likes, comments and shares: `reach`, `favorites`, `new_followers`, `profile_views`,
  `full_video_watched_rate`, `average_time_watched`, `total_time_watched`, the `retention` curve second by
  second, `impression_sources` (For You, Search, Follow, Personal Profile, Sound, Direct Message, Others) and `audience_types`.
  What TikTok did not report is omitted, not zeroed.
- [`GET /audience`](../api/audience.md) — who follows the account (countries, cities, ages, genders),
  daily followers gained and lost, profile actions, and `activity_by_hour`: how many of your followers
  are online in each hour of the day. That last one is the honest answer to "when should I publish", and
  it comes from your own audience rather than a generic best-time table. Window: up to 60 days, ending
  yesterday at the latest, trimmed automatically — read the `range` that comes back.
- The same call with `benchmark_category` adds the averages of your content category, so a 6% engagement
  rate stops being a number and becomes "above average for what I publish".

## Limits and gotchas

- **Daily cap:** 15 TikTok posts per connected account per rolling 24 h ([upload limits](./limit-of-uploads.md)).
- **Free plan:** TikTok uploads return `403`; you need a paid plan ([pricing & limits](../resources/pricing-and-limits.md)).
- **Rate limits:** TikTok allows **6 posts per minute** and **15 posts per day** per connected TikTok account.
- **`reached_active_user_cap` error:** a temporary TikTok platform limit. [Reconnect the TikTok account](./reached-active-user-cap-error.md) to move it to our current publishing route, which is not affected, or apply the inbox workaround.
- **Video privacy:** `privacy_level` works on video and photo posts alike, but TikTok decides **per account** which levels are available — a private account has no `PUBLIC_TO_EVERYONE`. Asking for one the account does not have fails with `tiktok_privacy_unavailable` and an error listing the ones it does have; omit the field on video and TikTok keeps the account's own default.
- **Failure codes to branch on:** TikTok accepts the job and reports the outcome
  afterwards, so a failure arrives on [Upload Status](../api/upload-status.md),
  not on the upload call. `tiktok_media_rejected` means TikTok refused the file
  itself (format, duration, frame rate, resolution or size) — fix the media, a
  retry of the same file will fail again. `tiktok_publish_failed` is everything
  else, including TikTok-side problems, and is worth retrying. Both refund the
  24 h allowance, because nothing was published.
- **Extra TikTok options:** attach a Commercial Music Library track (`tiktok_music_id`, see [Get TikTok Trending Music](../api/get-tiktok-music.md)), tag a place (`tiktok_location_id` + `tiktok_location_name`, see [Get TikTok Locations](../api/get-tiktok-locations.md)) or set a custom cover (`tiktok_cover_image_url`). Each of them needs its own capability — check `capabilities` first.
- **`tiktok_reconnect_required`:** a `400` on the comment, search or insights endpoints. The connection publishes fine but was never granted that permission; the account owner reconnects and it appears. See [Common Errors](../resources/common-errors.md#tiktok-reconnect-required).
- **Formats:** video MP4/MOV/WebM, up to 4 GB, 3–600 s, at least 360 px on the shortest side, 23–60 fps. Photos JPG/JPEG/WebP, up to 35 images of 20 MB each. See [Video Requirements](../api/video-requirements.md) and [Photo Requirements](../api/photo-requirements.md).


---
# How to Post to X (Twitter) with an API
URL: https://docs.upload-post.com/guides/post-to-x-twitter-api

# How do I post to X (Twitter) with an API?

Send a request to Upload-Post with `platform[]=x`: `POST https://api.upload-post.com/api/upload_text` for tweets and threads, `POST /api/upload` for video and `POST /api/upload_photos` for images. Upload-Post posts through its own X API access, so you don't need to buy an X API tier or manage X OAuth yourself. Just connect the X account once in the dashboard. Text longer than 280 characters is automatically split into a well-formatted thread (override with `x_long_text_as_post=true`).

## Steps

1. Create an account at [upload-post.com](https://www.upload-post.com) and generate an API key under **API Keys** ([Authentication](./authentication.md)).
2. Connect the X account at [Manage Users](https://app.upload-post.com/manage-users) (or via a [white-label JWT link](./user-profile-integration.md) for your users).
3. Post a tweet:

<Tabs groupId="lang">
<TabItem value="curl" label="cURL">

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'user="test"' \
  -F 'platform[]=x' \
  -F 'title="This is my tweet content!"' \
  -X POST https://api.upload-post.com/api/upload_text
```

</TabItem>
<TabItem value="python" label="Python">

```python
import requests

response = requests.post(
    "https://api.upload-post.com/api/upload_text",
    headers={"Authorization": "Apikey your-api-key-here"},
    data={
        "user": "test",
        "platform[]": "x",
        "title": "This is my tweet content!",
    },
)
print(response.json())
```

</TabItem>
<TabItem value="js" label="JavaScript">

```javascript
const form = new FormData();
form.append("user", "test");
form.append("platform[]", "x");
form.append("title", "This is my tweet content!");

const response = await fetch("https://api.upload-post.com/api/upload_text", {
  method: "POST",
  headers: { Authorization: "Apikey your-api-key-here" },
  body: form,
});
console.log(await response.json());
```

</TabItem>
</Tabs>

## Threads, video and images

- **Threads:** just send long text. Paragraphs are grouped up to 280 chars per tweet and posted as a thread, with media attached to the first tweet. Details in [Upload Text](../api/upload-text.md).
- **Video:** `POST /api/upload` with `platform[]=x` ([Upload Video](../api/upload-video.md#x-twitter)).
- **Images:** `POST /api/upload_photos` takes up to 4 images per tweet; more images are split across a thread controlled by `x_thread_image_layout` ([Upload Photo](../api/upload-photo.md)).

## Useful X parameters

Full list in the [Upload Video](../api/upload-video.md#x-twitter) and [Upload Text](../api/upload-text.md) references:

| Parameter | What it does | Default |
|-----------|--------------|---------|
| `x_title` | X-specific text (falls back to `title`) | `title` |
| `x_long_text_as_post` | Publish long text as a single post instead of a thread | `false` |
| `reply_to_id` | Reply to an existing tweet | none |
| `community_id` | Post into an X community | none |
| `reply_settings` | Who can reply: `following`, `mentionedUsers`, `subscribers`, `verified` | none |
| `tagged_user_ids` | Tag up to 10 users in media | `[]` |
| `first_comment` / `x_first_comment` | Auto-reply under the post | none |
| `scheduled_date` | ISO-8601 date to [schedule the post](../api/schedule-posts.md) | none |

:::warning URLs are stripped from X posts by default
X bills ~$0.200 per post containing a URL vs $0.015 without (13×), so Upload-Post removes clickable URLs from captions, titles and first comments on every X post. To publish posts **with** links, enable the [X Links add-on](./x-links-addon.md). Details: [Character Limits](../resources/character-limits.md#x-twitter-character-limits).

This bites hardest on `first_comment` / `x_first_comment`: a first comment that is
**only** a link becomes empty once the URLs are removed, so no reply is created at
all. The main post still publishes and the upload still succeeds — the response
carries a `warnings` entry saying the first comment was dropped. Either enable the
add-on, or give the first comment some text of its own.
:::

## Limits and gotchas

- **Daily cap (per connected X profile, rolling 24 h) is per plan:** Free/Basic 10, Professional 20, Advanced/Business 30 ([upload limits](./limit-of-uploads.md)).
- **Quote tweets with media are rejected.** Post the quote as text-only via Upload Text ([details](../api/upload-video.md#x-twitter)).
- **Replies can 403** on X's Pay-Per-Use tier when the account never engaged with the author ([details](../api/upload-video.md#x-twitter)).


---
# How to Upload Videos to YouTube with an API
URL: https://docs.upload-post.com/guides/post-to-youtube-api

# How do I upload videos to YouTube with an API?

Send a `multipart/form-data` request to Upload-Post's `POST https://api.upload-post.com/api/upload` with the video file (or URL), a `title` (**required** for YouTube), `user` and `platform[]=youtube`. Upload-Post ships its own dedicated YouTube API quota, so you don't need a Google Cloud project, YouTube Data API keys or a quota-increase request. You just connect the YouTube channel once via Google OAuth in the dashboard.

## Steps

1. Create an account at [upload-post.com](https://www.upload-post.com) and generate an API key under **API Keys** ([Authentication](./authentication.md)).
2. Connect the YouTube channel at [Manage Users](https://app.upload-post.com/manage-users) via Google OAuth (or a [white-label JWT link](./user-profile-integration.md) for your users).
3. Upload:

<Tabs groupId="lang">
<TabItem value="curl" label="cURL">

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'video=@/path/to/your/video.mp4' \
  -F 'title="Your Video Title"' \
  -F 'description="Your video description"' \
  -F 'user="test"' \
  -F 'platform[]=youtube' \
  -F 'tags[]=tutorial' \
  -F 'tags[]=howto' \
  -X POST https://api.upload-post.com/api/upload
```

</TabItem>
<TabItem value="python" label="Python">

```python
import requests

response = requests.post(
    "https://api.upload-post.com/api/upload",
    headers={"Authorization": "Apikey your-api-key-here"},
    files={"video": open("/path/to/your/video.mp4", "rb")},
    data=[
        ("title", "Your Video Title"),
        ("description", "Your video description"),
        ("user", "test"),
        ("platform[]", "youtube"),
        ("tags[]", "tutorial"),
        ("tags[]", "howto"),
    ],
)
print(response.json())
```

</TabItem>
<TabItem value="js" label="JavaScript">

```javascript

const form = new FormData();
form.append("video", new Blob([fs.readFileSync("/path/to/your/video.mp4")]), "video.mp4");
form.append("title", "Your Video Title");
form.append("description", "Your video description");
form.append("user", "test");
form.append("platform[]", "youtube");
form.append("tags[]", "tutorial");
form.append("tags[]", "howto");

const response = await fetch("https://api.upload-post.com/api/upload", {
  method: "POST",
  headers: { Authorization: "Apikey your-api-key-here" },
  body: form,
});
console.log(await response.json());
```

</TabItem>
</Tabs>

## YouTube Shorts

There is no separate Shorts endpoint: YouTube automatically classifies a video as a Short when it is **3 minutes (180 seconds) or less** and **vertical (9:16) or square (1:1)**. Upload it exactly like any other video.

## Useful YouTube parameters

Full list in the [Upload Video reference](../api/upload-video.md#youtube):

| Parameter | What it does | Default |
|-----------|--------------|---------|
| `youtube_title` / `youtube_description` | YouTube-specific title/description (fall back to `title`) | `title` |
| `tags[]` | Video tags | `[]` |
| `categoryId` | YouTube category | `"22"` |
| `privacyStatus` | `public`, `unlisted` or `private` | `public` |
| `thumbnail` / `thumbnail_url` | Custom thumbnail (file or URL, ≤ 2 MB; **not supported for Shorts**) | none |
| `youtube_playlist_id` | Playlist ID(s) to add the video to after publishing (comma-separated) | none |
| `youtube_subtitle_file_{N}` + `youtube_subtitle_language_{N}` | Subtitle tracks (SRT/VTT/…) | none |
| `containsSyntheticMedia` | Declare AI-generated content | `false` |
| `selfDeclaredMadeForKids` | COPPA declaration | `false` |
| `scheduled_date` | ISO-8601 date to [schedule the post](../api/schedule-posts.md) | none |

## Limits and gotchas

- **`title` is required** for YouTube uploads.
- **Daily cap:** YouTube videos are capped per connected channel per rolling 24 h; see [upload limits](./limit-of-uploads.md).
- **Big files:** use [`async_upload=true`](./async-uploads.md) and poll [Upload Status](../api/upload-status.md); synchronous requests fall back to background after 59 s.
- **Quota:** you no longer need your own Google Cloud quota ([background here](./youtube-quota-explained.md)).
- **Formats:** see [Video Requirements](../api/video-requirements.md).


---
# Rate Limits & Polling Best Practices
URL: https://docs.upload-post.com/guides/rate-limits

# Rate Limits & Polling Best Practices

Upload-Post applies rate limits at multiple levels to protect the service and the social media platforms. This guide explains each limit and how to design your integration to work within them.

## API Rate Limits

Every authenticated API response includes rate limit headers:

| Header | Description |
|--------|-------------|
| `X-RateLimit-Limit` | Maximum requests allowed in the current window |
| `X-RateLimit-Remaining` | Requests remaining in the current window |
| `X-RateLimit-Reset` | Unix timestamp when the window resets |

When you exceed the limit, the API returns **HTTP 429 Too Many Requests**. Wait until the `X-RateLimit-Reset` timestamp before retrying.

### Limits per plan

The per-minute window depends on your plan, and grows with the number of profiles on the account (**+2 requests/min and +10 requests/10 min per profile**), so multi-brand accounts are not capped like a single-profile one:

| Plan | Requests / minute | Requests / 10 minutes |
|------|------------------|-----------------------|
| Free | 60 | 300 |
| Professional | 100 | 500 |
| Advanced | 200 | 1,000 |
| Business | 500 | 2,500 |

Example: an Advanced account with 100 profiles gets 200 + 100 × 2 = **400 requests/min**. Hard ceiling: 1,000 requests/min and 5,000 requests/10 min. Requests made from the dashboard (web session) use a separate, higher allowance.

The exact limit that applies to your key is always in the `X-RateLimit-Limit` header of every response. The first request after a long idle period may report the Free tier while the API resolves your plan; the following requests apply your tier.

## Upload Status Polling

When using `async_upload=true`, you need to poll the [Upload Status](../api/upload-status) endpoint to get the result. Here are the recommended intervals:

### Polling Strategy

```
1. Submit upload → get request_id
2. Wait 5 seconds (initial delay)
3. Poll GET /api/uploadposts/status?request_id=<ID>
4. If status is "pending", "queued", or "processing" → wait 10 seconds → repeat step 3
5. If status is "completed" or "failed" → done
```

### Recommended Polling Intervals

| Scenario | Poll Interval | Max Wait |
|----------|--------------|----------|
| Photo uploads | Every 5–10 seconds | 2 minutes |
| Video uploads (small, < 50 MB) | Every 10 seconds | 5 minutes |
| Video uploads (large, > 50 MB) | Every 15 seconds | 10 minutes |
| Scheduled posts (after scheduled time) | Every 30 seconds | 10 minutes |
| Queued posts | Every 60 seconds | Until next queue slot |

### Status Cache TTLs

The status endpoint uses internal caching. Here is how often the status value is refreshed:

| Status | Cache TTL | Meaning |
|--------|-----------|---------|
| `queued` | 2 seconds | The upload is waiting for a worker |
| `pending` | 2 seconds | Accepted but not yet started |
| `processing` | 3 seconds | At least one platform is actively uploading |
| `completed` | 5 minutes | All platforms finished successfully |
| `failed` | 5 minutes | All platforms failed |

**Key takeaway:** Polling faster than every 5 seconds for non-terminal states is unnecessary — the cache refreshes every 2–3 seconds. For terminal states (`completed`/`failed`), the result is cached for 5 minutes, so subsequent polls are fast.

### Alternative: Use Webhooks

Instead of polling, configure a [webhook](../api/webhooks) to receive a `POST` notification when the upload completes. This is more efficient for high-volume integrations:

```json
{
  "channels": { "webhook": true },
  "webhook_url": "https://your-server.com/webhook"
}
```

The webhook fires once per platform per upload, so you get immediate notification without polling.

## Upload Rate Limits

### Per-Request Duplicate Protection

The API prevents duplicate uploads using idempotency keys and rate limiting:

- **Same content to same platform**: If you submit an identical upload (same user, platform, and content hash) within a short window, the API returns the existing request instead of creating a duplicate.
- **Idempotency-Key header**: Send a unique `Idempotency-Key` or `X-Idempotency-Key` header to ensure exactly-once processing, even across retries.

### Daily Upload Caps (per platform per account)

Each social account has a daily hard cap based on platform limits. Exceeding these returns **HTTP 429**:

| Platform | Max posts / 24h |
|----------|----------------|
| Instagram | 50 |
| TikTok | 15 |
| LinkedIn | 150 |
| YouTube | 10 |
| Facebook | 25 |
| X (Twitter) | 50 |
| Threads | 50 |
| Pinterest | 20 |
| Reddit | unavailable (`reddit_unavailable`) |
| Bluesky | 25 videos / 10 GB (video quota) |

These are rolling 24-hour windows per social account, not per API key.

### Monthly API Usage

Your plan determines how many API upload calls you can make per month:

| Plan | Monthly uploads |
|------|----------------|
| Free | 10 |
| Paid plans | See [pricing](https://upload-post.com/pricing) |

Check your current usage with `GET /api/uploadposts/me` — the response includes `api_usage.count`.

## API Key Brute-Force Protection

Failed authentication attempts (invalid API keys) are rate-limited per IP:

- After **10 consecutive failed attempts** with the same invalid key, the IP is blocked for **5 minutes**.
- The API returns **HTTP 429** during the block period.

## Best Practices

1. **Use webhooks** instead of polling when possible — they're instant and don't consume rate limit budget.
2. **Respect `X-RateLimit-Remaining`** — stop sending requests when it reaches 0.
3. **Use exponential backoff** on 429 responses — don't hammer the API after hitting a limit.
4. **Set `async_upload=true`** for all uploads — synchronous uploads timeout after 59 seconds anyway.
5. **Send `Idempotency-Key`** for all upload requests — this protects you from duplicate posts if your HTTP client retries on timeout.
6. **Poll at the recommended intervals** — faster polling just returns cached results and wastes your rate limit budget.


---
# Error: Reached Active User Cap
URL: https://docs.upload-post.com/guides/reached-active-user-cap-error

# Error: `{"code":"reached_active_user_cap"}`

If you've encountered this error, don't worry. This is not an issue with your account, your content, or our platform's stability. It's a temporary limitation from the TikTok API.

This error means that the **daily limit of active users** allowed by TikTok for our application has been reached.

**Recommended fix: reconnect the TikTok account.** The new publishing route is **not governed by this cap**, so posts go out directly — no inbox step.

Go to [Manage Users](https://app.upload-post.com/manage-users) and tap **Connect TikTok**. API calls do not change: same endpoints, same `platform[]=tiktok`, same fields. Reconnecting also unlocks the TikTok options in [Upload Video](../api/upload-video.md#tiktok) (music, location, custom covers). `privacy_level` still works on video and photos — TikTok decides which levels the account may use — and you can still send a draft with `post_mode=MEDIA_UPLOAD` (alias `tiktok_upload_to_draft=true`).

**Workaround without reconnecting:** send `post_mode=MEDIA_UPLOAD`. The file goes to the **TikTok inbox**; you finish and publish in the app. This path is **not affected by the cap**, for videos and photos.

```bash
curl --location 'https://api.upload-post.com/api/upload' \
  --header 'Authorization: ApiKey YOUR_API_KEY' \
  --form 'user="your_profile"' \
  --form 'platform[]="tiktok"' \
  --form 'title="My caption"' \
  --form 'video=@"/path/to/video.mp4"' \
  --form 'post_mode="MEDIA_UPLOAD"'
```

For photos, send the same `post_mode="MEDIA_UPLOAD"` field to the [photo upload endpoint](/api/upload-photo).

**What to expect with `MEDIA_UPLOAD`:**
- The post lands in your **TikTok inbox / drafts** — you must open the **TikTok app** to confirm and publish it.
- **Your caption does not travel with a video draft.** TikTok's inbox endpoint accepts only the video file itself — no title, caption, privacy or other metadata — so the draft arrives without your text and you paste it in the app before publishing. This also applies to posts that reach the inbox via the automatic fallback below: the caption you sent is delivered only when the post publishes directly. (Photo/carousel drafts are the exception — TikTok does accept `title`/`description` for those.)
- The draft's caption field may show an app-branding hashtag prefilled by TikTok; you can delete or keep it when editing.
- See the full reference in the [video](/api/upload-video) and [photo](/api/upload-photo) API docs.

### Automatic fallback — and how to detect it in your code

Since June 2026 you usually won't even see this error on uploads: when the cap is hit, a `DIRECT_POST` upload is **automatically retried as `MEDIA_UPLOAD`** instead of failing. Your content still reaches TikTok — it lands in your **inbox/drafts** for you to publish from the app — and the API response reports `success: true`.

Because the post is *not* live yet in that case, the response gives you a machine-readable signal to detect the switch: the TikTok result includes **`"fallback_to_inbox": true`**.

```json
{
  "success": true,
  "publish_id": "v_inbox_file~v2.1234567890",
  "fallback_to_inbox": true,
  "note": "Video sent to your TikTok inbox for manual posting"
}
```

For **video** uploads the returned id also starts with **`v_inbox_file`** (a direct publish id starts with `v_pub_`); photo ids use different prefixes, so rely on the `fallback_to_inbox` flag rather than the id shape. If your workflow needs to distinguish "published live" from "waiting in the inbox", check the flag on every TikTok result. A notification email is also sent to the account owner when the fallback happens.

Since **August 2026** the `fallback_to_inbox` flag is also **persisted with the upload record**, so it appears on the per-platform results returned by [`GET /api/uploadposts/status`](/api/upload-status) and [`GET /api/uploadposts/history`](/api/upload-history). This matters for **scheduled and async uploads**, where there is no synchronous response to inspect: polling `status` or reading `history` now shows the flag too. (Uploads recorded before the change carry no flag — for those, a **video** inbox delivery usually shows `post_url: "Video sent to Inbox (No Public URL)"`, while photo inbox deliveries have `post_url: null`.)

**Exception:** accounts on the **Business plan** are excluded from the automatic fallback — they receive the `reached_active_user_cap` error directly instead, so their automations can decide what to do.

**Opting out:** any account can disable the fallback per upload by sending **`disable_inbox_fallback=true`** (video and photo uploads). When the cap is hit, the upload then fails instead of landing in the inbox: the TikTok result carries `error_code: "reached_active_user_cap"` plus a human-readable `error` message, so your automation can match the code and reschedule. Prefer this if your integration has its own retry or rescheduling logic: an inbox draft **cannot be deleted through TikTok's API** (only the account owner can discard it in the app), and unpublished drafts count toward TikTok's limit of 5 pending inbox shares per 24h (`spam_risk_too_many_pending_share`).

### What is a "Daily Active User"?

In this context, a "daily active user" is anyone who uses our application to interact with the TikTok API on a given day. TikTok sets a cap on how many unique users can do this through a single application (like ours) within a 24-hour period.

### What should you do?

*   **Your account and content are safe.** This is not a penalty or a block on your account.
*   **Best long-term fix: [reconnect the TikTok account](https://app.upload-post.com/manage-users).** Reconnected accounts publish through our new route, which is not subject to the cap, so direct publishing keeps working every day.
*   **Keep posting today with `post_mode=MEDIA_UPLOAD`.** This sends your content to your TikTok inbox so you can publish it from the app, bypassing the cap (see the workaround above).
*   **Or wait and retry.** The user cap is reset by TikTok every 24 hours, so waiting a few hours before posting directly again also works.
*   If the error persists for more than 24 hours, please try again the next day.

### Why does this happen?

To manage their platform's resources, TikTok imposes a daily usage quota on every application that connects to its API. Due to the rapid growth of our user community, we are sometimes hitting this maximum allowed number of daily users.

### What are we doing about it?

We changed how we publish to TikTok: the new route runs on an API surface that
is not governed by the daily active-user cap. New TikTok connections, and any
account you reconnect, are routed there automatically, so this error stops
appearing for them.

In parallel, we remain in direct communication with TikTok's developer support
team to **request an increase in our daily user quota** for the old route.

Unfortunately, the timeline for this increase is determined by TikTok, and we cannot expedite their internal review process. We appreciate your patience as we work to resolve this for good.

Thank you for your understanding. We are committed to providing a reliable service and are doing everything we can to support our growing community.


---
# White-label Integration
URL: https://docs.upload-post.com/guides/user-profile-integration

# White-label Integration Guide

![Profiles diagram](/img/profiles-diagram.png)

This guide explains how to integrate Upload-Post directly into your own platform. This allows your users to connect their social media accounts securely through Upload-Post, enabling your platform to manage their profiles and posts via the API on their behalf.

## Integration Flow Overview

The core idea is to create a unique profile within Upload-Post for each user on your platform who wants to connect their social accounts. You then let the user link their accounts in one of two ways:

*   **Hosted connect page** (fastest): generate a secure `access_url` and send the user there. You can brand it with your logo, title, texts and language — no frontend work needed. This is the flow described step by step below.
*   **Your own connect page** ([Connect API](../api/connect-api.md)): build the connection UI inside your product, on your own domain, with your own design. Your page requests each platform's authorize URL from the API and Upload-Post handles the OAuth exchange behind the scenes.

Once linked (either way), your platform interacts with the Upload-Post API using the user's unique identifier.

## Step-by-Step Integration

### Step 1: Create a User Profile

For each user on your platform, you need to create a corresponding profile in Upload-Post. This is done by making a `POST` request to the `/api/uploadposts/users` endpoint.

*   **Requirement:** You must provide a unique `username` in the request body. This `username` should be a stable identifier that links the Upload-Post profile back to the user on your platform (e.g., your internal user ID).
*   **Authentication:** Remember to include your `Authorization: Apikey YOUR_API_KEY` header.
*   **Result:** The API will respond with details of the created profile, confirming the `username`.

➡️ **See details:** [Create User Profile API Reference](../api/user-profiles.md#create-user-profile)

### Step 2: Generate the Secure JWT URL

Once the profile exists, you need to generate a secure URL that your user will use to connect their social media accounts. Make a `POST` request to the `/api/uploadposts/users/generate-jwt` endpoint.

*   **Requirement:** In the request body, provide the same unique `username` (from Step 1). You can also include the following optional fields:
    *   `redirect_url`: A URL to which the user will be redirected after linking their account.
    *   `logo_image`: A URL to a logo image for branding on the linking page.
    *   `redirect_button_text`: (Optional) The text to display on the redirect button after linking. Defaults to "Logout connection".
    *   `connect_title`: (Optional) Custom title text for the connection page.
    *   `connect_description`: (Optional) Custom description text for the connection page.
    *   `platforms`: (Optional) List of platforms to show for connection. Defaults to all supported platforms.
    *   `show_calendar`: (Optional) Whether to show the calendar view on the connection page. Defaults to `true`.
    *   `readonly_calendar`: (Optional) When `true`, shows only a read-only calendar view. Users cannot edit, delete, or create posts, and cannot connect or disconnect social accounts. Ideal for sharing a content calendar with end clients. Defaults to `false`.
    *   `language`: (Optional) Forces the connection page language for this profile. Supported values: `en`, `es`, `de`, `fr`, `pt`, `pl`, `tr`. When omitted, the page auto-detects the visitor's browser language and falls back to English.
    *   `ui_labels`: (Optional) A flat object of connect-page i18n keys → replacement strings, for white-label integrations that need to override UI text beyond `connect_title`/`connect_description`/`redirect_button_text`. Max 100 entries, values max 300 chars. See [Custom UI Labels](../api/user-profiles.md#custom-ui-labels).
*   **Authentication:** Include your `Authorization: Apikey YOUR_API_KEY` header.
*   **Result:** The API will return a JSON object containing an `access_url`. This URL contains a secure token (JWT) valid for 48 hours.

➡️ **See details:** [Generate JWT URL API Reference](../api/user-profiles.md#endpoint-generate-jwt-url)

**Quick troubleshooting (common integration mistakes):**
- If `generate-jwt` returns `404`, call `GET /api/uploadposts/users` and verify the profile `username` exists.
- If profile creation returns `403`, you hit your plan profile limit. Resolve limits before retrying JWT generation.
- Always treat profile creation and JWT generation as two explicit checked steps (do not ignore non-2xx responses).

### Step 3: User Connects Accounts

Redirect your user to the `access_url` obtained in Step 2. This URL will open the Upload-Post connection interface, guiding the user through the process of securely connecting their desired social media accounts (like Instagram, TikTok, Facebook, etc.) to their profile.

**Enhanced Connect Experience:**
- **Professional Navigation**: Tab-based interface for easy switching between account connection and calendar view
- **Calendar View** (if enabled): Users can view their scheduled posts and upload history directly from the connect page
- **Customizable Interface**: You can control the branding, title, and available features through the JWT parameters
- **Secure OAuth Flows**: Upload-Post handles all authentication and token storage securely

The connection URL is valid for **48 hours**, giving users ample time to complete the linking process.

**Alternative — build your own connect page:** if you want full control over the connection experience (your domain, your design, your copy), skip the hosted page entirely and use the [Connect API](../api/connect-api.md). Your page calls `POST /api/uploadposts/oauth/{platform}/start` with the profile JWT from Step 2, redirects the user to the returned `authorize_url`, and Upload-Post sends them back to your `redirect_url` when the account is connected. The hosted page and the Connect API can be mixed freely — both link accounts to the same profile.

### Step 4: Manage User Content via API

After the user successfully connects their accounts in Step 3, your platform can now use other Upload-Post API endpoints to manage content on their behalf.

*   When making calls to endpoints like [Upload Photo](../api/upload-photo.md) or [Upload Video](../api/upload-video.md), you will typically include the user's unique `username` (the one you used in Step 1 and 2) in the request parameters to specify which profile's connected accounts should be used.
*   You can also retrieve the list of profiles and their connected accounts using the `GET /api/uploadposts/users` endpoint.

➡️ **See details:** [Get User Profiles API Reference](../api/user-profiles.md#get-user-profiles)

## Connecting Accounts (manual credentials)

Some platforms (Discord, Telegram, Slack, Mastodon, Nostr, Lemmy, Dev.to, Hashnode, WordPress, Whop, Listmonk) connect with a **manual credential** — an incoming webhook, API token, or key — instead of OAuth, with no browser redirect. The full step-by-step for each lives in its own guide:

➡️ **[Connecting Social Accounts](./connecting-accounts.md)**

## Read-Only Calendar for Clients

If you're an agency managing content for clients, you can generate a read-only calendar link that lets your clients view their scheduled posts without being able to edit anything.

Use the `readonly_calendar` parameter when generating the JWT:

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/generate-jwt \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "client_profile_123",
    "readonly_calendar": true,
    "logo_image": "https://youragency.com/logo.png",
    "connect_title": "Your Content Calendar"
  }'
```

The returned `access_url` will show:
- Social media channel for each post
- Scheduled date and time
- Visual (photo/video preview)
- Post text/caption

The client **cannot**:
- Edit, delete, or reschedule posts
- Connect or disconnect social accounts
- Access any other section of Upload-Post

This is ideal for agencies that need to share content calendars with end clients for approval or visibility.

## Authentication

All API requests related to user profile management (`/api/uploadposts/users` and `/api/uploadposts/users/generate-jwt`) require authentication using your API Key. Include it in the `Authorization` header for every request:

```
Authorization: Apikey YOUR_API_KEY
```

Replace `YOUR_API_KEY` with the actual API key provided to you.

*(Note: The `/api/uploadposts/users/validate-jwt` endpoint uses Bearer token authentication, as detailed in its specific documentation).*

## Next Steps

With the user profiles created and accounts linked, explore the other API references to start managing content:

*   [Upload Photo API Reference](../api/upload-photo.md)
*   [Upload Video API Reference](../api/upload-video.md)


---
# X Links Add-on
URL: https://docs.upload-post.com/guides/x-links-addon

# X Links Add-on

By default, Upload-Post **strips every URL from posts published to X (Twitter)** before
they are published. This keeps your X usage on X's cheaper pricing tier — X bills roughly
`$0.200` per "Content: Create (with URL)" request versus `$0.015` for posts without a URL.
You can read the full stripping policy on the
[Character Limits page](../resources/character-limits.md#x-twitter-character-limits) and in the
[Upload Text reference](../api/upload-text.md).

The **X Links add-on** lets you keep URLs in your X posts instead of having them removed.

## What it does

When the add-on is active, parseable URLs are **no longer stripped** from your X posts —
captions, titles, and `first_comment` keep their links. This applies across every X path
(video, photo, text, scheduled, and retried posts).

Only posts that actually contain a link consume the add-on's quota. Normal link-free X posts
are unaffected and never count against it.

## Price

| Plan | Price | Billing |
| --- | --- | --- |
| X Links add-on | **$19/month (USD)** or **€18/month (EUR)** | Monthly only |

There is no annual option for this add-on yet.

## Quota: 50 link-posts per month

The add-on includes **50 link-posts per month**, counted **globally per account**. The 50/month
pool is **shared across all of your X profiles** — it is **not** a per-profile allowance.

Only posts that include a link consume the quota. Link-free posts do not.

### What happens when the cap is reached

When you reach your 50 link-posts for the billing cycle, the behaviour **reverts to the
default**: URLs are stripped from any further X posts until the quota resets at the start of
your next billing cycle.

## How to enable it

The X Links add-on requires an **active paid subscription** and is added on top of your
existing subscription:

1. Open your **subscription / billing settings** in the Upload-Post app.
2. Find the **X Links add-on** and choose **"Add to plan"**.

Once added, the add-on takes effect on your existing subscription and your monthly quota
begins.

## Coexists with other add-ons

The X Links add-on works alongside other add-ons (for example, the extra-profiles add-on).
You can have several add-ons active on the same subscription at once.


---
# Understanding YouTube API Quota Limits
URL: https://docs.upload-post.com/guides/youtube-quota-explained

# Understanding YouTube API Quota Limits

> **⚠️ DEPRECATED:**  
> We have now received our own dedicated YouTube API quota. You no longer need to configure your own Google Cloud project. All YouTube features are available directly from our platform without any extra setup.

We believe in being transparent with our community about the challenges we face and the solutions we implement. This document explains the current situation regarding YouTube's API quota and introduces a new feature that gives you more control.

### The Challenge: YouTube's API Quota

Every application that interacts with YouTube, including ours, is subject to a daily API quota. This quota determines how many actions (like uploads, comments, or data requests) can be performed through our platform each day.

Due to the incredible growth of our user base, we are frequently reaching the limit of our current quota. This can sometimes result in temporary service disruptions for YouTube-related features.

### What We Are Doing About It

For the past six months, we have been in ongoing discussions with YouTube's leadership team to request a significant increase in our daily quota. We believe a higher quota is essential to reliably serve our growing community.

Unfortunately, this process has been slower than anticipated, and we are still awaiting a final decision. We are persistently following up and providing all necessary information to make our case.

### Our Solution: Use Your Own Google Cloud Project

To provide a stable and reliable solution while we wait for the quota increase, we have implemented a new feature: **you can now connect your own Google Cloud project to our application.**

By doing this, you will use your own personal YouTube API quota instead of our shared, limited quota.

#### Benefits of This Approach:

*   **Reliability:** You are no longer affected by our shared quota reaching its limit. As long as your personal quota has not been exceeded, your YouTube actions will succeed.
*   **Control:** You have full visibility and control over your own API usage through your Google Cloud Console.
*   **No More Waiting:** This immediately solves the issue for you, without having to wait for our negotiations with YouTube to conclude.

### How to Connect Your Own Google Cloud Project

Here is a step-by-step guide to connect your own project and use your personal API quota:

1.  **Go to the [Google Cloud Console](https://console.cloud.google.com/).**
2.  **Create a new project** or select an existing one.
3.  **Enable the YouTube Data API v3** for your project. You can find this in the "APIs & Services" > "Library" section.
4.  Navigate to **"APIs & Services" > "Credentials"**.
5.  Click **"Create Credentials"** and select **"OAuth client ID"**.
6.  If prompted, configure the **"OAuth consent screen"**:
    *   Select **"External"** for the user type.
    *   Provide an app name (e.g., "My Upload-Post Connection"), your user support email, and developer contact information. You can use your own email address for all fields.
7.  For the **"Application type"**, choose **"Web application"**.
8.  Under **"Authorized redirect URIs"**, click **"ADD URI"** and paste the following URL:
    ```
    https://app.upload-post.com/youtube-callback
    ```
9.  Click **"Create"**. You will now see your **Client ID** and **Client Secret**.
10. **Copy these credentials.** You will need to enter them into our application to complete the connection.

After obtaining your Client ID and Client Secret, you can securely enter them in your account settings within our application to finalize the connection.

Thank you for your patience and understanding. We remain committed to resolving the quota issue at the platform level and will keep you updated on our progress.


---
# Upload-Post Social Media API
URL: https://docs.upload-post.com/introduction

# Upload-Post Social Media API

Welcome to Upload-Post

Upload-Post is your go-to API solution for seamless content management across multiple social media platforms. Our API simplifies the process of uploading and managing your social media content, making it easy for developers and creators to automate their social media presence.

## What is Upload-Post?

Upload-Post provides a streamlined API for uploading and managing content across popular social media platforms. Through our simple REST API, you can upload videos and photos to multiple platforms with minimal effort. Our platform handles all the complexities of social media APIs, allowing you to focus on creating great content.

The API follows REST principles, with endpoints representing different types of content uploads accessible via standard HTTP methods. All data is exchanged in JSON format, making integration straightforward and efficient.

## Supported Social Networks

Upload-Post currently supports 22 social media platforms and business tools:

### TikTok
- Upload videos
- Upload photo posts (multi-image carousels)
- Set the post text: videos take a `title`; photo posts take both a `title` and a `description`
- Set privacy level and commercial content disclosure
- Control comments, duets and stitches
- Add a first comment
- Read and moderate the comments on a post: reply, hide, like, pin, delete
- Audience insights, per-video retention and category benchmarks
- Keyword and hashtag research before writing the caption

### Instagram
- Upload photos and carousels
- Upload Reels and Stories
- Mixed photo + video carousels
- Tag collaborators, users and locations
- Add a first comment

### LinkedIn
- Share articles
- Post updates
- Upload images

### YouTube
- Upload videos
- Set video metadata
- Manage playlists

### Facebook
- Post updates
- Share links
- Upload photos and videos

### X (Twitter)
- Post tweets
- Upload media
- Thread support

### Threads
- Post text updates
- Upload photos (up to 10 per post, auto-threaded beyond that)
- Upload video
- Mixed photo + video carousels
- Add a topic tag and a first comment

### Pinterest
- Create image pins (single or carousel)
- Create video pins with a custom cover image
- Target a specific board
- Add a destination link and alt text

### Reddit
- Posting is currently **unavailable**. Uploads, OAuth connect, and comments return HTTP 503 `error_code: "reddit_unavailable"`.

### Bluesky
- Post text updates
- Upload images
- Upload video
- Thread support
- Decentralized social networking

### Discord
- Post text messages to a channel
- Upload images (up to 10 per message)
- Upload video
- Connects via a channel incoming webhook (no OAuth)

### Telegram
- Post text messages to a chat or channel
- Upload images (single or album)
- Upload video
- Connects with your own bot (bot token + chat id, no OAuth)

### Google Business Profile
- Publish standard updates
- Create event posts
- Share offer posts with coupon codes
- Add call-to-action buttons (Book, Order, Shop, Learn More, Sign Up, Call)
- Attach photos to posts

### Slack
- Post text messages to any channel
- Connects via a channel Incoming Webhook URL (no OAuth)

### Mastodon
- Post text updates
- Upload images and video
- Works with any Mastodon instance (instance URL + access token)

### Nostr
- Publish NIP-01 kind:1 notes
- Signed with your nsec key and broadcast to your relays

### Lemmy
- Publish text posts
- Upload photos
- Works with any Lemmy community (instance, username and password)

### Dev.to
- Publish markdown articles to your DEV Community account
- Connects with a DEV API key

### Hashnode
- Publish markdown blog posts to your Hashnode publication
- Connects with a Personal Access Token and publication ID

### WordPress
- Create posts with text, photos and video on self-hosted WordPress
- Connects via an application password (WordPress REST API)

### Whop
- Send text posts to a Whop forum experience
- Connects with a Company API key

### Listmonk
- Create and dispatch email newsletter campaigns to a Listmonk list
- Connects with your instance URL, API user and list id

We integrate directly with each platform's official APIs to ensure reliable, secure, and compliant content management.

## Beyond Publishing

Upload-Post is not write-only. Alongside the upload endpoints you get:

- **Analytics**: per-account and per-post metrics. See [Get Analytics](./api/get-analytics.md).
- **Comments**: read the comments on a post, reply, or delete them, on Instagram, Facebook, YouTube, LinkedIn and TikTok. The same endpoint lists the replies under a comment (`comment_id`) and hides, likes or pins one (`/comments/action`). See [Comments (all platforms)](./api/comments.md), plus [Instagram private & public replies](./api/instagram-comments.md).
- **Direct messages**: send DMs and list conversations. See [Instagram DMs](./api/instagram-dms.md).
- **AutoDMs**: monitor a post for keywords in the comments and answer automatically. See [AutoDMs](./api/autodms.md).
- **Scheduling**: hand us a publish time instead of running your own cron queue.
- **Video processing**: FFmpeg jobs for adapting media per platform.
- **Paid ads**: run [ChatGPT Ads](./api/chatgpt-ads.md) from the same API key — connect an OpenAI Ads account, create campaigns, ad groups and ads, and pull performance insights. Ad spend runs on your own OpenAI billing.

Comments are available for Instagram, Facebook, YouTube, LinkedIn and TikTok; DM features are currently Instagram-only. Analytics cover Instagram, TikTok, YouTube, Threads, Pinterest, Reddit, Facebook, X (Twitter) and LinkedIn; Discord, Telegram, and the credential-based channels (Slack, Mastodon, Nostr, Lemmy, Dev.to, Hashnode, WordPress, Whop, Listmonk) do not expose analytics.

## MCP Server for AI Agents

Upload-Post ships an official, open-source **Model Context Protocol (MCP)** server, hosted at `https://mcp.upload-post.com/mcp`. Connect it to ChatGPT, claude.ai, Claude Desktop, Claude Code, Cursor, or any MCP-compatible agent, and your assistant can publish, schedule and analyze social media without you writing a REST client.

It supports both API key auth and OAuth 2.1 with PKCE, so agents can authorize on their own. In ChatGPT it is also available as a reviewed app in the app directory — [add Upload-Post in ChatGPT](https://chatgpt.com/plugins/plugin_asdk_app_6a1fed02ff5c8191b4060c5807221f26) in one click, no Developer mode needed. See the [MCP Server guide](./guides/mcp-server-integration.md).

## API-First Approach

At Upload-Post, we believe in an API-first approach. This means:

- **Developer-Focused**: Everything is designed with developers in mind
- **Simple Integration**: Easy to integrate into any application
- **Clear Documentation**: Comprehensive guides and examples
- **Reliable Service**: Stable and secure API endpoints

## Getting Started with Upload-Post

1. **Register with Upload-Post**
   - Create your account at [upload-post.com](https://www.upload-post.com)
   - Get your API key from the dashboard

2. **Connect Social Accounts**
   - Link your social media accounts
   - Grant necessary permissions

3. **Explore the API**
   - Check out our [API Reference](./api/reference)
   - Try our [Quickstart Guide](./quickstart)

4. **Start Uploading**
   - Use our simple endpoints to upload content
   - Monitor your upload status
   - Manage your content across platforms

## Why Choose Upload-Post?

- **Simple Integration**: Get started in minutes with our straightforward API
- **Reliable Service**: Built on stable, production-ready infrastructure
- **Cost-Effective**: Start with 10 free uploads per month
- **Developer Support**: Comprehensive documentation and support
- **Secure**: Enterprise-grade security for your content and API keys

## Next Steps

- Check out our [Quickstart Guide](./quickstart) to make your first API call
- Explore our [API Reference](./api/reference) for detailed endpoint documentation

## Need Help?

- Contact our support team at info@upload-post.com
- Follow us on X (Twitter) [@vcaverog](https://x.com/vcaverog) for updates
- Check our [FAQ](./resources/faq) for common questions


---
# Quickstart Guide
URL: https://docs.upload-post.com/quickstart

# Quickstart Guide

This guide will help you get started with the Upload-Post API in minutes.

![Quick Start](/img/quick-start.png)

## Prerequisites

- An Upload-Post account
- Connected TikTok and/or Instagram accounts
- API key from your dashboard

## Step 1: Create Your Account

1. Visit [upload-post.com](https://www.upload-post.com)
2. Sign up for a new account

## Step 2: Connect Your Social Media Accounts

1. Navigate to [User Management](https://app.upload-post.com/manage-users)
2. Create a profile with a name of your choice (this name will be used in API calls)
3. Click on one of the social media networks
4. Follow the authentication flow for the selected platform
5. Grant necessary permissions for content upload

## Step 3: Generate Your API Key

1. Go to the API Keys section. [Api Keys](https://app.upload-post.com/api-keys)
2. Click "Generate New API Key"
3. Copy and save your API key securely

## Step 4: Make Your First API Call

### Upload a Video to TikTok

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'video=@/path/to/your/video.mp4' \
  -F 'title="My First TikTok Video"' \
  -F 'user="test"' \
  -F 'platform[]=tiktok' \
  -X POST https://api.upload-post.com/api/upload
```

### Upload a Photo to Instagram

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'photos[]=@/path/to/your/image1.jpg' \
  -F 'user="test"' \
  -F 'platform[]=instagram' \
  -F 'title="My First Instagram Post"' \
  -F 'description="Hello Instagram!"' \
  -X POST https://api.upload-post.com/api/upload_photos
```

## Next Steps

- Check out our [API Reference](../api/reference) for detailed endpoint documentation
- Explore our [SDK Examples](../sdk-examples) for code samples in your preferred programming language

## Need Help?

- Check our [FAQ](resources/faq) for common questions
- Contact our support team at info@upload-post.com


---
# Social Media Character Limits
URL: https://docs.upload-post.com/resources/character-limits

# Social Media Character Limits

This guide summarizes the most relevant text limits for each social network supported by Upload-Post. Keep these constraints in mind when building payloads so posts are accepted without truncation.

## Platform-Specific Character Limits

### Facebook Character Limits

| Property | Description |
| --- | --- |
| post | 63,206 characters maximum |
| title | Reels title – 255 characters maximum |

### Instagram Character Limits

| Property | Description |
| --- | --- |
| post | 2,200 characters maximum |
| altText | 1,000 characters maximum per image |
| comment | 2,196 characters maximum |

### LinkedIn Character Limits

| Property | Description |
| --- | --- |
| post | 3,000 characters maximum |
| title | 400 characters maximum (UTF-16 code units; an emoji counts as 2) – the media title of a video or document post. A line break sent as CRLF counts as 2, so a title that measures 399 locally can still exceed the limit. Longer titles are trimmed with an ellipsis. |

### TikTok Character Limits

| Property | Description |
| --- | --- |
| post | 2,200 characters maximum (UTF-16 code units; an emoji counts as 2) |
| title (photo posts) | 90 characters maximum (UTF-16 code units) |
| description (photo posts) | 4,000 characters maximum (UTF-16 code units) |

### Pinterest Character Limits

| Property | Description |
| --- | --- |
| post | 800 characters maximum |
| title | 100 characters maximum |
| link | 2,048 characters maximum |
| altText | 500 characters maximum |

### Reddit Character Limits

Reddit posting is currently unavailable (HTTP 503 `error_code: "reddit_unavailable"`).

### Threads Character Limits

| Property | Description |
| --- | --- |
| post | 500 characters maximum (UTF-8 bytes; an emoji is 4 bytes) |
| post (`threads_long_text_as_post`) | Up to 10,000 characters as a single post |

### X (Twitter) Character Limits

| Property | Description |
| --- | --- |
| post | 280 characters maximum |
| post (Premium) | 25,000 characters maximum for Premium and Premium Plus accounts |
| altText | 1,000 characters maximum per image |
| subTitleName | 150 characters maximum |

:::warning URLs are automatically removed from X posts
Upload-Post strips every URL that X would turn into a clickable link from any
caption, title, description, or `first_comment` sent to X (Twitter), before
the post is published. This applies to video, photo, and text uploads via
every endpoint and via the scheduler.

**Why:** X charges `$0.200` per "Content: Create (with URL)" request versus
`$0.015` for posts without a URL — a 13× surcharge. To keep pricing predictable,
every parseable link is removed before the tweet leaves Upload-Post.

**What gets stripped:** anything X's parser recognises as a URL:

- Schemed URLs: `https://…`, `http://…`, `ftp://…`, `ws://…`
- `www.host.tld[/path]`
- Common shorteners: `t.co/…`, `bit.ly/…`, `tinyurl.com/…`, `lnkd.in/…`,
  `youtu.be/…`, etc.
- Bare hostnames with a path: `example.com/posts/1`
- IPv4 with a path: `192.168.1.1:8080/admin`
- Markdown link syntax: `[text](https://…)` (the URL portion is removed)

**What is NOT stripped:** anything that X does not parse as a link does not
trigger the surcharge, so we leave it alone — e.g. `example[.]com`,
`example(dot)com`, `hxxp://…`, unicode-dot lookalikes (`example．com`), or
bare domains with no path. These display as plain text in the tweet and are
billed at the normal `$0.015` rate.

If you need to share a link, put it in your X profile bio or render it visibly
inside an image/video. To keep URLs in your X posts instead of having them stripped,
see the [X Links add-on](../guides/x-links-addon.md).
:::

### Bluesky Character Limits

| Property | Description |
| --- | --- |
| post | 300 characters maximum |
| images | Up to 4 images per post |
| altText | Supported |

### Discord Character Limits

| Property | Description |
| --- | --- |
| message | 2,000 characters maximum (longer text is truncated) |
| attachments | Up to 10 images per message |

### Telegram Character Limits

| Property | Description |
| --- | --- |
| message (text) | 4,096 characters maximum (longer text is truncated) |
| caption (photo/video) | 1,024 characters maximum |
| album | Up to 10 photos per `sendMediaGroup` |

### YouTube Character Limits

| Property | Description |
| --- | --- |
| post | 5,000 characters maximum (also validated as 5,000 UTF-8 bytes) |
| youTubeOptions > title | 100 characters maximum |
| youTubeOptions > tags | 500 characters total, 2+ characters each |
| youTubeOptions > subTitleName | 150 characters maximum |

### Snapchat Character Limits

| Property | Description |
| --- | --- |
| Spotlight description | 500 characters maximum |
| Saved Story title | 45 characters maximum |

**Notes:**
- Stories are ephemeral (24 hours) and don't support text captions
- Saved Stories are permanent on your public profile
- Spotlight posts are permanent and reach wider audiences
- Only one media item (video or image) allowed per post
- Hashtags are supported and clickable in Spotlight posts

## Content Restrictions

### Banned Hashtags

### Google Business Profile Character Limits

| Field | Limit |
|-------|-------|
| Post summary (title) | 1,500 characters |
| Event title | 58 characters |
| Offer coupon code | 58 characters |
| Offer terms | 1,000 characters |
| CTA URL | Standard URL length |

**Notes:**
- Google Business Profile supports one photo per post via the API.
- Video uploads are supported (max 30 seconds / 28 MB).
- Product posts cannot be created via the API.

Upload-Post validates content against a list of prohibited hashtags before posting to Instagram. Posts containing any of these hashtags will be rejected with a validation error. The complete list of banned hashtags includes:

**A:** anorexia, alone, a$$, antivax, abdl, addmysc, adulting, always, armparty, asiagirl

**B:** beautyblogger, bikinibody, boho, blogladrona, brain, besties, bikinibod

**C:** costumes, curvygirls, cancer

**D:** date, dating, desk, dm

**E:** elevator, edm, endme

**F:** followtrain, followtrains

**G:** graffitiigers, girlsonly, gloves

**H:** hardworkpaysoff, happythanksgiving, humpday, hustler, hotgirls

**I:** iphonegraphy, italiano, ifb

**K:** kansas, killingit, kissing, kill, killme, killyourself, kys

**M:** master, models, mustfollow, milf, midget

**N:** nasty, newyearsday

**P:** petite, petitegirls, pushups, payme

**S:** saltwater, shit, shower, single, singlelife, skype, snap, snapchat, snapchatme, snowstorm, sopretty, stranger, streetphoto, sunbathing, swole, suicide, suicideawareness

**T:** tag4like, tanlines, teens, teen, thought, todayimwearing

**U:** undies, unbalanced

**V:** valentinesday

**W:** workflow

**Y:** youngmodel, yolo

If your content includes any of these hashtags, remove them before submitting your request to avoid validation errors.

## API Considerations

- Upload-Post validates payload sizes before sending them to social networks whenever limits are known. Requests that exceed the documented limits return a validation error.
- Some platforms might truncate overlong text instead of rejecting it (Meta products and YouTube occasionally do this). Inspect the per-platform response inside `results` to confirm the final content.
- For channels with strict limits such as X, consider shortening URLs in your application prior to calling the Upload-Post API.

## Updates and Changes

Social networks regularly adjust their limits. We keep this page aligned with the latest behavior we observe in production, but you should also:

- Check Upload-Post API responses for detailed error messages about rejected posts.
- Subscribe to our release notes for platform updates.
- Revisit this reference periodically, especially before large content campaigns.


---
# Common Errors
URL: https://docs.upload-post.com/resources/common-errors

# Common Errors

This guide covers the most common errors you might encounter when using Upload-Post and how to resolve them. Errors are organized by category to help you quickly find solutions.

## Session Expired {#session-expired}

Session and authentication errors occur when the connection between Upload-Post and your social media account has been broken. This is usually easy to fix by reconnecting your account.

### Common Session Errors

| Error Message | Platform | Solution |
|---------------|----------|----------|
| `error_code: "reddit_unavailable"` (HTTP 503) | Reddit | Reddit posting, OAuth, and comments are currently unavailable. Do not send `platform[]=reddit`. |
| "Your [Platform] session has expired" | All | [Reconnect your account](https://app.upload-post.com/manage-users) |
| "Token expired and refresh failed" | All | [Reconnect your account](https://app.upload-post.com/manage-users) |
| "The session has been invalidated because the user changed their password" | Facebook/Instagram | Reconnect after password change |
| "Error validating access token" | Facebook/Instagram | Verify permissions and reconnect |
| "Your X session has expired and could not be refreshed" | X (Twitter) | Reconnect your X account |
| "User has not authorized application" | Various | Grant permissions and reconnect |
| "Unauthorized" | Various | Reconnect your account |

### How to Fix Session Errors

1. **Go to [Manage Users](https://app.upload-post.com/manage-users)**

2. **Find the affected account** - Look for accounts with warning indicators

3. **Disconnect the account** - Click the disconnect/remove button next to the account

4. **Reconnect the account** - Click "Connect" and complete the authorization flow

5. **Grant all permissions** - Make sure to approve all requested permissions during reconnection

:::tip
If you recently changed your password on a social media platform, you'll need to reconnect that account in Upload-Post.
:::

### Meta checkpoint ("log in to instagram.com and follow the instructions") {#checkpoint}

Instagram, Facebook and Threads sometimes answer a publish with
`error_code: "account_checkpoint_required"` — Graph API error 190 / subcode 459,
*"You cannot access the app till you log in to www.instagram.com and follow the
instructions given"*. Meta is asking the **account owner** to clear a security
prompt on the platform itself; the token is not the problem and reconnecting
alone does not clear it.

Because every attempt fails until the prompt is cleared, Upload-Post marks the
account as needing reauthentication on the **first** checkpoint (it used to
take five failures over five hours). From then on:

- New posts for that account are rejected at precheck with
  `error_code: "account_reauth_required"`, `failure_stage: "precheck"`; no credit
  is consumed and nothing is sent to Meta.
- The account owner receives the reconnection email once.
- Once a day Upload-Post probes flagged Instagram and Threads accounts. If Meta
  has stopped returning the checkpoint (the owner logged in and completed the
  prompt), the flag is cleared automatically and posting resumes with no
  further action. Reconnecting the account from
  [Manage Users](https://app.upload-post.com/manage-users) also clears it immediately.

---

## Account Blocked {#account-blocked}

These errors occur when there's an issue with your account on the social media platform itself. Upload-Post cannot fix these - you need to resolve them directly on the platform.

### Common Account Status Errors

| Error Message | Platform | What It Means |
|---------------|----------|---------------|
| "Sessions for the user are not allowed because the user is not a confirmed user" | Facebook | Account needs verification |
| "The YouTube account of the authenticated user is suspended" | YouTube | Account suspended by YouTube |
| "Your account is temporarily locked" | X (Twitter) | X has locked your account |
| "The Instagram account is restricted or inactive" | Instagram | Instagram has restricted your account |
| "Action suspected as spam. Activity is restricted" | Instagram | Spam detection triggered |
| "The user used for authentication is suspended" | Various | Account suspended on platform |

### How to Fix Account Blocked Errors

These issues must be resolved directly on the social media platform:

#### Facebook/Instagram Account Not Confirmed

1. Go to [facebook.com](https://www.facebook.com) and log in
2. Check for any verification prompts or security notices
3. Go to **Settings & Privacy** > **Settings** > **Personal Details**
4. Verify your email and phone number are confirmed
5. Visit [Facebook Account Quality](https://www.facebook.com/accountquality/) to check for restrictions
6. After resolving issues, reconnect in Upload-Post

#### X (Twitter) Account Locked

1. Go to [twitter.com](https://twitter.com) or [x.com](https://x.com) and log in
2. Follow the prompts to unlock your account (may require phone verification)
3. Once unlocked, reconnect in Upload-Post

#### YouTube Account Suspended

1. Go to [YouTube](https://www.youtube.com) and sign in
2. Check the [YouTube Help Center](https://support.google.com/youtube/) for suspension appeals
3. Follow YouTube's process to restore your account

#### Instagram Restricted

1. Open the Instagram app and log in
2. Look for any notification banners or prompts
3. Follow Instagram's instructions to verify your identity
4. After restrictions are lifted, reconnect in Upload-Post

#### Temporary restrictions ("Action suspected as spam. Activity is restricted") {#account-restricted}

Instagram answers with this error (Graph API error `4` / subcode `2207051`) when its spam
detector flags an account — typically after many feed posts in a short time from a
newly connected account. It is a **temporary block on Instagram's side**, usually
24–48 hours; your access token is still valid and Stories keep working.

When Upload-Post receives it:

- Your Instagram account **stays connected** to the profile. Reconnecting does not
  lift the block, and every retry can extend it.
- Upload-Post pauses feed posts for that account for 24 hours and rejects them at
  precheck with `error_code: "account_restricted"`, `failure_stage: "precheck"` and a
  `restricted_until` timestamp. Upload credits are not consumed for these rejections.
- Stories are not paused.
- You receive one email explaining the pause.

To resolve it: open the Instagram app, complete any "review your account" prompt,
wait 24–48 hours, then resume at a lower cadence (a few feed posts per day, varied
captions). If you know the restriction has already been lifted, reconnecting the
account in Upload-Post ends the pause early.

#### Other paused accounts (`account_restricted` at precheck) {#paused-accounts}

The same pause mechanism is used for every platform verdict that is known to
keep failing until something on the platform changes. Instead of failing post
after post (and burning credits and platform quota), Upload-Post pauses the
account for a window that matches the platform's own reset and rejects new
posts at precheck with `error_code: "account_restricted"`,
`failure_stage: "precheck"`, `restriction_reason`, `restricted_until` and
`retry_after_seconds`. Credits are refunded, the account stays connected, and
reconnecting it from Manage Users ends the pause early.

| `restriction_reason` | Platform | What triggered it | Pause |
|----------------------|----------|-------------------|-------|
| `spam_restricted` | Instagram | "Action suspected as spam. Activity is restricted" | 24 h |
| `daily_upload_limit` | YouTube | "This channel has reached the number of videos it may upload today" (a per-channel limit set by YouTube, normally reset around midnight Pacific Time) | 2 h, then retried |
| `rate_limited` | Facebook | "We limit how often you can post, comment or do other things in a given amount of time" — only when the Facebook login manages a single Page (the limit is per Page) | 1 h |
| `publish_limit` | Instagram | "Daily publishing limit reached" (Instagram's 24-hour rolling API publishing limit) | 30 min |
| `identity_confirmation` | Facebook | "Confirm your identity before you can publish as this Page" — only when the Facebook login manages a single Page (the verdict is per Page) | 24 h (email sent) |
| `permission_denied` | YouTube | "You do not have permission to perform this action" | 24 h (email sent) |
| `spam_risk_*` | TikTok | TikTok anti-spam verdicts, see [TikTok Spam-Risk Errors](#tiktok-spam-risk) | 1 h – 7 days |

Instagram Stories are exempt from the `spam_restricted` pause (Meta's spam block
is feed-only and stories keep working), but **not** from the quota pauses
(`publish_limit`, `rate_limited`, `daily_upload_limit`), where stories count
against the same allowance.

Throughput limits (`daily_upload_limit`, `rate_limited`, `publish_limit`) clear
by themselves and do not send an email; the pause is only visible in the
upload response, `/api/uploadposts/status` and the account badge in the
dashboard. The windows are deliberately shorter than the platform's nominal
reset: the limits are partly rolling, so a post after the pause often goes
through, and if the platform refuses again the account is simply paused
again. Verdicts that need the owner to act (`identity_confirmation`,
`permission_denied`) send one email. Don't re-queue a paused account before
`restricted_until`: the request will not reach the platform anyway.

:::warning
Upload-Post cannot bypass platform restrictions. You must resolve these issues directly with the social media platform before reconnecting.
:::

---

## Configuration & Permissions {#configuration-permissions}

These errors occur when your account is connected but requires additional configuration or permissions.

### Common Configuration Errors

| Error Message | Platform | Solution |
|---------------|----------|----------|
| "No Facebook Pages found for your account" | Facebook | Connect a Facebook Page (personal profiles not supported) |
| "Multiple Facebook Pages found. Please select a Page" | Facebook | Select which Page to post to |
| "Facebook Page ID is required for text posts" | Facebook | Configure your profile to select a Page |
| "Couldn't get a Facebook Page access token for Page ID..." | Facebook | Reconnect with proper Page permissions |
| "Pinterest account not found or not configured" | Pinterest | Configure Pinterest in your profile |
| "Board not found" | Pinterest | Select a valid Pinterest board |
| "You are not permitted to access that resource" | Various | Check your role/permissions on the account |
| "This site doesn't allow you to save Pins" | Pinterest | The target site blocks Pinterest pins |
| "quote_tweet_id cannot be used when uploading media to X. Remove it and retry." | X (Twitter) | Quote tweets and media are mutually exclusive — drop `quote_tweet_id` or remove the media |
| "X rejected this reply (403)... not permitted to reply to this author on X's Pay-Per-Use tier" | X (Twitter) | Reply to an author the account has engaged with (this is a platform restriction, not a session error — reconnecting won't help) |
| `error_code: "tiktok_reconnect_required"` | TikTok | The TikTok connection cannot serve this endpoint. [Reconnect the account](#tiktok-reconnect-required) |
| `error_code: "platform_not_supported"` | Various | That network cannot answer this question yet. [What it means](#platform-not-supported) |

### TikTok: `tiktok_reconnect_required` {#tiktok-reconnect-required}

**HTTP 400.** The profile has a working TikTok connection — it publishes fine —
but that connection does not carry what the endpoint you called needs. TikTok
decides an account's permissions **at the moment it is connected**, so an account
connected a while ago simply never got them.

```json
{
  "success": false,
  "message": "Profile 'your_profile' has no TikTok connection that supports this endpoint. Reconnect your TikTok account from Manage Users.",
  "error_code": "tiktok_reconnect_required"
}
```

**Which endpoints return it**

| Endpoint | Capability needed |
| :--- | :--- |
| [Comments](/api/comments) with `platform=tiktok` (list / create / delete) | `comments` |
| [Comment replies](/api/comments#list-comments) (`comment_id`) and [hide / like / pin](/api/comments#hide-like-or-pin-a-comment) | `comments` |
| [Suggestions](/api/suggestions) with `type=keywords` | `trend_search` |
| [Audience Insights](/api/audience) (with its category benchmark), [Suggestions](/api/suggestions) with `type=hashtags`, the TikTok per-post breakdown in [Analytics](/api/get-analytics) | `profile_analytics` |
| [Get TikTok Settings](/api/get-tiktok-settings), [Music](/api/get-tiktok-music), [Locations](/api/get-tiktok-locations) | a current connection |

**How to fix it**

1. Read the profile's [`capabilities`](/api/user-profiles#account-capabilities)
   from `GET /api/uploadposts/users` to see what the connection actually has.
2. Have the account owner reconnect TikTok from
   [Manage Users](https://app.upload-post.com/manage-users), or send them through
   a [white-label connect link](/guides/user-profile-integration).
3. Nothing in your integration changes — same endpoints, same field names. The
   capability appears in `capabilities` once the account is back.

**Do not confuse it with a session error.** `reauth_required` / HTTP `409` means
the token expired; `tiktok_reconnect_required` means the token is fine and the
permission was never granted. Both are fixed by reconnecting, but only the first
one blocks publishing.

**Uploading is never blocked by this.** Sending an optional field the connection
does not support still publishes the post and returns a `warnings` array naming
what was ignored — including a `first_comment` on TikTok, which is skipped with a
warning instead of failing the publish.

### `platform_not_supported` {#platform-not-supported}

**HTTP 400.** Upload-Post's API is one endpoint per **question**, with a
`platform` telling it which network to ask — never one URL per network. When a
network cannot answer that question yet, you get this code instead of an empty
`200`, and the message names the ones that can:

```json
{
  "success": false,
  "message": "'instagram' does not support audience insights yet. Supported: tiktok.",
  "error_code": "platform_not_supported"
}
```

It is not a misconfiguration and reconnecting the account will not fix it: the
integration for that network simply is not built yet. Branch on the
`error_code`, not on a hard-coded list of platforms — when the network starts
answering, the same call you already make begins returning data, with no change
on your side.

Endpoints that can answer it today:
[`GET /api/uploadposts/audience`](/api/audience),
[`GET /api/uploadposts/suggestions`](/api/suggestions) and
[`POST /api/uploadposts/comments/action`](/api/comments#hide-like-or-pin-a-comment)
(where the field is called `error` rather than `message`).

### How to Fix Configuration Errors

#### Facebook Page Issues

Facebook requires you to post to a **Facebook Page**, not a personal profile. The Facebook API does not support posting to personal profiles.

1. **Create a Facebook Page** (if you don't have one):
   - Go to [Create a Page](https://www.facebook.com/pages/create/)
   - Follow the setup wizard

2. **Connect your Page to Upload-Post**:
   - Go to [Manage Users](https://app.upload-post.com/manage-users)
   - Disconnect your Facebook account
   - Reconnect and select the Page you want to post to
   - Make sure you have **Admin** or **Editor** role on the Page

3. **Select the correct Page in your profile**:
   - Go to your Upload-Post profile settings
   - Select the Facebook Page from the dropdown

:::info Required Permissions
To post to a Facebook Page, you need:
- **Admin** or **Editor** role on the Page
- Grant "pages_manage_posts" permission during connection
- Grant "pages_read_engagement" permission
:::

#### Pinterest Board Issues

1. Make sure you have at least one board in your Pinterest account
2. Go to your Upload-Post profile and select the correct board
3. If the board was recently created, try disconnecting and reconnecting Pinterest

---

## Content Format {#content-format}

These errors occur when your media doesn't meet the platform's requirements for size, format, or aspect ratio.

### Common Content Errors

| Error Message | Platform | Solution |
|---------------|----------|----------|
| "Unsupported image size" | Various | Use supported dimensions |
| "Invalid image aspect ratio" | Instagram | Use ratio between 4:5 and 1.91:1 |
| "Video longer than 2 minutes" | X (Twitter) | Shorten video or upgrade X account |
| "TikTok rejected the media format" | TikTok | Use MP4/MOV format |
| "One or more tags are invalid" | Various | Remove special characters from tags |
| "Your post must contain post flair" | Reddit | Add required flair to your post |
| "Media could not be fetched from the provided URL" | Various | Use a publicly accessible URL |
| "Downloaded file is too small" | Various | Check URL points to valid media |
| "Collaborator usernames are invalid" | Instagram | Use valid public usernames without @ |
| "Your title is too long for LinkedIn" | LinkedIn | See [LinkedIn media title limit](#linkedin-media-title) below |

### LinkedIn: title too long on video and document posts {#linkedin-media-title}

LinkedIn caps the media title of a video or document post at **400 characters** and
rejects the whole post above that. Two things make a title that looks short go over:

- **Line breaks travel as CRLF.** A `\n` in a form field reaches the API as `\r\n`,
  two characters. A title with 4 line breaks counted as 396 locally arrives as 400.
- **Emoji count twice.** LinkedIn measures in UTF-16 code units, so 🌍 is two
  characters, not one.

Upload-Post now trims an over-long title to 400 characters and adds a `warnings`
entry to the response rather than failing the post. To keep the title intact, put
the long text in `description` (the post body), which has a much larger limit, and
send a short `title`.

See [Character Limits](./character-limits) for every platform's caps.

### Media Requirements by Platform

| Platform | Image Format | Max Image Size | Video Format | Max Video Duration |
|----------|--------------|----------------|--------------|-------------------|
| Instagram | JPG, PNG | 8 MB | MP4, MOV | 60 min (Feed), 90 sec (Reels) |
| Facebook | JPG, PNG, GIF | 10 MB | MP4, MOV | 240 min |
| TikTok | - | - | MP4, MOV | 10 min |
| X (Twitter) | JPG, PNG, GIF | 5 MB | MP4 | 2 min 20 sec* |
| LinkedIn | JPG, PNG | 8 MB | MP4 | 10 min |
| YouTube | - | - | MP4, MOV, AVI | 12 hours |
| Pinterest | JPG, PNG | 32 MB | MP4, MOV | 15 min |

*X Premium users may have longer video limits

### Instagram Aspect Ratio Guidelines

- **Square**: 1:1
- **Portrait**: 4:5 (recommended for Feed)
- **Landscape**: 1.91:1
- **Stories/Reels**: 9:16

Images outside the 4:5 to 1.91:1 range will be rejected.

:::tip
For best results, use **1080x1350 pixels (4:5 ratio)** for Instagram feed posts.
:::

### Fixing Media URL Issues

If you're getting "Media could not be fetched" errors:

1. **Check the URL is publicly accessible** - Open it in an incognito browser window
2. **Don't use private/restricted URLs** - Google Drive links must be set to "Anyone with the link"
3. **Use direct file URLs** - The URL should end with a file extension like `.jpg` or `.mp4`
4. **Check file size** - Very small files (< 1KB) often indicate a broken link

---

## Rate Limits {#rate-limits}

These errors occur when you've hit posting limits or the platform is experiencing temporary issues.

### Common Rate Limit Errors

| Error Message | Platform | Solution |
|---------------|----------|----------|
| "This YouTube channel has reached the number of videos it may upload today" (`youtube_channel_daily_limit`) | YouTube | Per-channel limit set by YouTube, normally reset around midnight Pacific Time. Upload-Post [pauses the channel](#paused-accounts) for 2 h and retries. |
| "Service Unavailable (503)" | Various | Retry in a few minutes |
| "Temporary issue. We retried 4 times but it still failed" | Instagram | Platform issue - retry later |
| "Fatal" / "Unexpected error" | Various | Platform issue - retry later |
| "Rate limit reached" | Various | Wait before making more requests |

### Platform Daily Limits

| Platform | Daily Posting Limit |
|----------|---------------------|
| Instagram | ~50 posts per day |
| TikTok | 15-20 videos per day |
| LinkedIn | ~150 posts per day |
| Pinterest | 25 pins per day |
| Reddit | Varies by subreddit |
| YouTube | 30 videos per day |

### TikTok Spam-Risk Errors {#tiktok-spam-risk}

TikTok enforces per-account anti-spam caps on API posting. These are **rolling 24-hour limits on your TikTok account**, not an Upload-Post restriction — no retry or reconnect can lift them early.

| Error | What it means | How to fix |
|-------|---------------|------------|
| `spam_risk_too_many_pending_share` | Your TikTok account has reached the cap of **5 unpublished (pending) inbox drafts in 24 hours**. This happens when videos are sent to the TikTok inbox but never published from the app. | Open the TikTok app and **publish or discard your pending drafts** (Inbox → System notifications), then wait for the 24h window to roll. |
| `spam_risk_too_many_posts` | Your TikTok account reached its daily API posting cap (typically **~15 posts per day per account**; the exact number varies per creator). | Wait for the rolling 24h window. Spread posts across the day or across accounts. |
| `spam_risk_user_banned_from_posting` | TikTok has banned this account from posting via API. | Contact TikTok support from the affected account. |

**Upload-Post parks restricted accounts.** After one of these verdicts, Upload-Post stops sending that TikTok account to TikTok for a while and answers new uploads immediately with `error_code: "account_restricted"`, `failure_stage: "precheck"`, `restriction_reason`, `restricted_until` and `retry_after_seconds` (the credit is refunded). Windows: `spam_risk_user_banned_from_posting` → 7 days, `spam_risk_too_many_posts` → 1 hour (TikTok's limit is rolling, so we only pause long enough to stop retries). Post-level verdicts (`spam_risk_text`, `spam_risk`) and the inbox-draft backlog (`spam_risk_too_many_pending_share`) never park the account — fix the text / publish the drafts and retry. The account owner also receives an email when the account is parked, and reconnecting the TikTok account in Upload-Post clears the pause (for example after TikTok support lifted the restriction). Don't re-queue a parked account before `restricted_until`: the request will not reach TikTok anyway.

Official TikTok references: [Content Sharing Guidelines](https://developers.tiktok.com/doc/content-sharing-guidelines) · [Content Posting API — Upload Video](https://developers.tiktok.com/doc/content-posting-api-reference-upload-video)

:::tip Inbox mode and pending drafts
If your posts are being delivered to the TikTok inbox (for example while the daily [active-user cap](/guides/reached-active-user-cap-error) fallback is active), remember to publish them from the app — 5 unpublished drafts will block further uploads for 24h with `spam_risk_too_many_pending_share`.
:::

### Pinterest video Pins: "Sorry! This site doesn't allow you to save Pins" {#pinterest-video-refusal}

Despite the wording, this answer (Pinterest error `code: 1`) has nothing to do
with the Pin's `link`: in production it only ever happens on **video** Pins,
right after the uploaded video's processing status turns `succeeded`, and the
same account usually publishes fine minutes later. Upload-Post treats it as a
transient Pinterest refusal and retries the identical Pin twice (after 15 s and
30 s) before reporting the error; nothing about the Pin is changed. If all
attempts are refused, retry the request a few minutes later.

### Bluesky Requirements {#bluesky-requirements}

| Error | What it means | How to fix |
|-------|---------------|------------|
| "Bluesky requires a verified email before uploading videos" (`unconfirmed_email`) | Bluesky-hosted accounts must have a **verified email** before the video service accepts uploads. Only the account owner can verify it. | In the Bluesky app: **Settings → Account → Email → Verify**, then retry the upload. |
| "Bluesky allows at most 4 images per post; the first 4 of N were published" | The AT Protocol schema hard-limits image posts to **4 images**. Upload-Post publishes the first four and reports the rest in the response's `warnings` array (`success` stays `true`). | Send 4 images or fewer if you need to choose which ones are published. |

Official Bluesky references: [Video uploads](https://docs.bsky.app/docs/tutorials/video) · [Creating posts](https://docs.bsky.app/docs/tutorials/creating-a-post)

### Handling Rate Limits

1. **Wait and retry** - Most limits reset at midnight UTC
2. **Spread posts throughout the day** - Avoid posting many items at once
3. **Use scheduling** - Schedule posts to spread them out automatically
4. **Check your usage** - View your posting history to track activity

:::info Automatic Rescheduling
When you hit a daily limit, Upload-Post may automatically reschedule your post for the next day. Check your scheduled posts to confirm.
:::

---

## Getting Help

If your error isn't listed here or you need additional assistance:

1. **Check our [FAQ](/resources/faq)** for general questions
2. **Review our [API Error Handling Guide](/guides/error-handling)** for technical details
3. **Contact support** at [info@upload-post.com](mailto:info@upload-post.com)

When contacting support, please include:
- The exact error message you received
- The platform you were posting to
- The type of content (text, photo, video)
- When the error occurred

---

## Platform Help Centers

If you need to resolve issues directly with a social media platform:

- [Facebook Help Center](https://www.facebook.com/help/)
- [Instagram Help Center](https://help.instagram.com/)
- [TikTok Support](https://support.tiktok.com/)
- [YouTube Help](https://support.google.com/youtube/)
- [X (Twitter) Help](https://help.x.com/)
- [LinkedIn Help](https://www.linkedin.com/help/linkedin)
- [Pinterest Help](https://help.pinterest.com/)
- [Reddit Help](https://support.reddithelp.com/)


---
# Frequently Asked Questions
URL: https://docs.upload-post.com/resources/faq

# Frequently Asked Questions

This FAQ covers the most common questions about Upload-Post. Questions are organized by category to help you find answers quickly.

---

## Connection & Authentication

### Where do I find my API Key?

1. Log in to your [Upload-Post Dashboard](https://app.upload-post.com/)
2. Navigate to the "API Keys" section
3. Click "Generate New API Key"
4. Copy and securely store your API key

Include your API key in the `Authorization` header of all API requests:

```bash
Authorization: Apikey your-api-key-here
```

### How do I connect my TikTok/Instagram/Facebook/YouTube/LinkedIn account?

1. Go to [Manage Users](https://app.upload-post.com/manage-users) in your dashboard
2. Click "Connect" next to the platform you want to add
3. Follow the OAuth authorization flow for that platform
4. Grant all requested permissions when prompted
5. Once connected, the account will appear in your profile

### How do I connect Discord?

Discord uses a **manual webhook** connection instead of OAuth:

1. In Discord, go to **Server Settings → Integrations → Webhooks → New Webhook**
2. Pick the channel to post to and click **Copy Webhook URL**
3. Send the webhook URL to Upload-Post:

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/discord/credentials \
  -H 'Authorization: Apikey YOUR_API_KEY' \
  -H 'Content-Type: application/json' \
  -d '{"profile_username": "myprofile", "webhook_url": "https://discord.com/api/webhooks/123/abc..."}'
```

Once connected, post with `platform[]=discord`. Discord supports text (max 2000 chars), up to 10 images per message, and video. See [Connecting Discord](/guides/connecting-accounts#connecting-discord-manual-credentials).

### How do I connect Telegram?

Telegram uses your **own bot** instead of OAuth:

1. Message [@BotFather](https://t.me/BotFather) in Telegram, send `/newbot`, and copy the **bot token**
2. Add the bot to your target channel or group **as an administrator**
3. Send the bot token and chat id to Upload-Post:

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/telegram/credentials \
  -H 'Authorization: Apikey YOUR_API_KEY' \
  -H 'Content-Type: application/json' \
  -d '{"profile_username": "myprofile", "bot_token": "123456:ABC...", "chat_id": "@my_channel"}'
```

Once connected, post with `platform[]=telegram`. Telegram supports text (max 4096 chars), single/multiple photos, and video (caption max 1024 chars). The bot **must be an admin** of the chat to post. See [Connecting Telegram](/guides/connecting-accounts#connecting-telegram-manual-credentials).

### Why do I get error 400 when connecting Instagram?

Error 400 when connecting Instagram usually means:

- **Account not confirmed**: Your Facebook/Instagram account needs email or phone verification. Go to [Facebook Account Quality](https://www.facebook.com/accountquality/) to check for issues.
- **Missing permissions**: During connection, make sure to approve ALL requested permissions.
- **Business account required**: Instagram API requires a Business or Creator account. With Instagram Login it does **not** need to be linked to a Facebook Page.

### How do I get my Facebook Page ID?

Use our API endpoint to retrieve all Facebook Pages associated with your account:

```bash
curl 'https://api.upload-post.com/api/uploadposts/facebook/pages' \
  -H 'Authorization: Apikey YOUR_API_KEY'
```

The response includes `page_id`, `page_name`, and the associated profile for each page.

:::info
Facebook API does not support posting to personal profiles—only Pages can be posted to. If you don't have a Page, create one at [facebook.com/pages/create](https://www.facebook.com/pages/create/).
:::

### Why do I have to choose a Facebook Page every time I post?

Connecting Facebook only links your account—it does **not** pick a destination Page. You must **select the target Page on each upload** by passing `facebook_page_id`:

```bash
-F 'facebook_page_id="123456789"'
```

If exactly one Page is connected, it is auto-selected and you can omit the parameter. If multiple Pages are connected and you don't pass `facebook_page_id`, the API returns an `available_pages` list so you can pick one. Use [Get Facebook Pages](/api/get-facebook-pages) to look up your `page_id` values, or [pin a Page to the profile](/api/facebook-page) so you never have to pass it. The Facebook daily cap is applied per Page, not per profile.

### How does JWT work for white-label integrations?

JWT (JSON Web Token) enables white-label integrations where your users connect their social accounts through your platform:

1. **Create a profile** for your user via `POST /api/uploadposts/users`
2. **Generate a secure URL** via `POST /api/uploadposts/users/generate-jwt`
3. **Redirect your user** to the returned `access_url`
4. The user connects their accounts through Upload-Post's interface
5. **Use the API** with the user's `username` to post on their behalf

The JWT URL is valid for **48 hours**. See the [White-label Integration Guide](/guides/user-profile-integration) for full details.

### How long do tokens last before I need to reconnect?

Token validity varies by platform:

| Platform | Typical Duration | Notes |
|----------|------------------|-------|
| TikTok | ~60 days | Auto-refreshes if used regularly |
| Instagram | ~60 days | May expire after password changes |
| Facebook | ~60 days | May expire after password changes |
| LinkedIn | ~60 days | Auto-refreshes if used regularly |
| YouTube | ~6 months | May require re-authorization |
| X (Twitter) | Long-lived | Usually stable unless revoked |

When a token expires, you'll receive a "session expired" error. Simply reconnect the account at [Manage Users](https://app.upload-post.com/manage-users).

---

## Limits & Quotas

### How many posts can I make per day on each platform?

Upload-Post enforces platform hard caps using a rolling 24-hour window to protect your accounts:

| Platform | Daily Limit (per account) |
|----------|---------------------------|
| Instagram | 50 posts |
| TikTok | 15 posts |
| LinkedIn | 150 posts |
| YouTube | 30 videos |
| Facebook | 25 posts |
| X (Twitter) | 50 posts |
| Threads | 50 posts |
| Pinterest | 20 pins |
| Reddit | unavailable (`reddit_unavailable`) |
| Bluesky | 25 videos / 10 GB (video quota) |

### What does error 429 (Too Many Requests) mean?

Error 429 indicates you've hit a rate limit. This can happen when:

1. **Monthly upload limit reached**: Your plan's monthly quota is exhausted
2. **Daily platform cap reached**: You've hit the 24-hour limit for a specific platform/account

Example response:
```json
{
  "success": false,
  "message": "Daily cap reached for instagram: 50/50 in last 24h",
  "violations": [{
    "platform": "instagram",
    "type": "hard_cap",
    "used_last_24h": 50,
    "cap": 50
  }]
}
```

**Solution**: Wait for the 24-hour window to roll over, or spread posts across multiple connected accounts.

### Are limits per account or per profile?

Limits are **per connected social account**, not per Upload-Post user or profile.

**Example**: If you manage 5 Profiles, and each has its own TikTok account connected, you get the full limit for each TikTok account (5 TikTok accounts × 15 posts = 75 TikTok posts per day total).

### What does "unlimited uploads" mean in the Basic plan?

"Unlimited uploads" refers to the number of API calls you can make per month through Upload-Post. However, you're still subject to:

- **Platform daily caps** (enforced per social account to protect against bans)
- **Profile limits** (based on your plan tier)
- **Platform-specific restrictions** (each social network's own rules)

### How many profiles can I connect on each plan?

Check [upload-post.com/pricing](https://www.upload-post.com/pricing) for the current limits per plan. You can see your current limit and usage via the `GET /api/uploadposts/users` endpoint, which returns a `limit` field showing your maximum allowed profiles.

---

## Video Uploads

### How do I upload YouTube Shorts vs regular videos?

YouTube automatically determines if a video is a Short based on:

- **Duration**: 3 minutes (180 seconds) or less
- **Aspect ratio**: Vertical (9:16) or square (1:1)

Simply upload your video normally to the `/api/upload` endpoint. YouTube will classify it as a Short if it meets these criteria.

```bash
curl -X POST https://api.upload-post.com/api/upload \
  -H 'Authorization: Apikey YOUR_API_KEY' \
  -F 'video=@short_video.mp4' \
  -F 'title="My YouTube Short"' \
  -F 'user="myprofile"' \
  -F 'platform[]=youtube'
```

:::warning
Custom thumbnails are **not supported for YouTube Shorts**—they only apply to standard YouTube videos.
:::

### What is the maximum video file size?

Maximum file sizes vary by platform:

| Platform | Max File Size | Max Duration |
|----------|---------------|--------------|
| TikTok | 4 GB (Upload-Post cap 3 GB) | 10 minutes |
| Instagram | 300 MB | 15 minutes |
| YouTube | 256 GB | 12 hours |
| LinkedIn | 500 MB | 30 minutes |
| Facebook | ~1 GB | 240 minutes |
| X (Twitter) | 8 GB (16 GB Premium) | 20 min (125 min Premium) |
| Threads | 1 GB | 5 minutes |
| Pinterest | 2 GB | 15 minutes |
| Reddit | unavailable (`reddit_unavailable`) | — |
| Bluesky | 300 MB | 10 minutes |

See [Video Requirements](/api/video-requirements) for detailed format specifications.

### Can I use a video URL instead of uploading the file?

Yes! Instead of uploading a file, you can pass a direct URL to your video:

```bash
curl -X POST https://api.upload-post.com/api/upload \
  -H 'Authorization: Apikey YOUR_API_KEY' \
  -F 'video="https://example.com/videos/myvideo.mp4"' \
  -F 'title="My Video"' \
  -F 'user="myprofile"' \
  -F 'platform[]=tiktok'
```

**Requirements for video URLs**:
- URL must be publicly accessible (test in an incognito browser)
- Should be a direct link to the file (ending in `.mp4`, `.mov`, etc.)
- Google Drive links must be set to "Anyone with the link"
- File must not be too small (< 1KB often indicates a broken link)

### Why is my video stuck in "processing"?

Videos may remain in processing status for several reasons:

1. **Long upload**: If sync upload takes > 59 seconds, it automatically switches to async processing
2. **Platform processing**: The social network is processing your video (especially for large files)
3. **Encoding issues**: The video format may need transcoding

**To check status**, use the Upload Status endpoint:
```bash
curl 'https://api.upload-post.com/api/uploadposts/status?request_id=YOUR_REQUEST_ID' \
  -H 'Authorization: Apikey YOUR_API_KEY'
```

Status values: `pending` → `in_progress` → `completed`

### How do I upload videos to TikTok drafts (MEDIA_UPLOAD mode)?

Prefer `post_mode=MEDIA_UPLOAD`. `tiktok_upload_to_draft=true` is an alias of the same draft mode — use either field on any TikTok account.

```bash
curl -X POST https://api.upload-post.com/api/upload \
  -H 'Authorization: Apikey YOUR_API_KEY' \
  -F 'video=@video.mp4' \
  -F 'title="Draft Video"' \
  -F 'user="myprofile"' \
  -F 'platform[]=tiktok' \
  -F 'post_mode="MEDIA_UPLOAD"'
```

:::info
In draft mode TikTok does **not** allow setting title, caption, privacy, or other metadata via API on video. The video uploads to TikTok drafts, and you add details in the TikTok app before publishing.
:::

---

## Photo & Carousel Uploads

### How do I upload carousels to Instagram/TikTok/Facebook?

Upload multiple photos using the `/api/upload_photos` endpoint with the `photos[]` array:

```bash
curl -X POST https://api.upload-post.com/api/upload_photos \
  -H 'Authorization: Apikey YOUR_API_KEY' \
  -F 'photos[]=@image1.jpg' \
  -F 'photos[]=@image2.jpg' \
  -F 'photos[]=@image3.jpg' \
  -F 'title="My Carousel"' \
  -F 'user="myprofile"' \
  -F 'platform[]=instagram'
```

**Mixed carousels** (photos + videos) are supported on **Instagram and Threads only**:
```bash
-F 'photos[]=@image.jpg' \
-F 'photos[]=@video.mp4'
```

### How many photos can I include in a carousel?

| Platform | Max Photos per Post |
|----------|---------------------|
| Instagram | 10 items (photos/videos mixed) |
| Threads | 10 items (photos/videos mixed) |
| TikTok | Multiple (photo slideshow) |
| Facebook | Multiple |
| Pinterest | 5 carousel images |
| Bluesky | 4 images |
| X (Twitter) | 4 images |
| Reddit | unavailable (`reddit_unavailable`) |
| Discord | 10 images per message |
| Telegram | 10 photos per album |

For **X (Twitter)**, use `x_thread_image_layout` to control how images are distributed across tweets when posting more than 4 images. For **Threads**, use `threads_thread_media_layout` to control how media items are distributed across posts when posting more than 10 items.

### What image resolution/size should I use?

Recommended specifications by platform:

| Platform | Recommended Size | Max File Size | Formats |
|----------|------------------|---------------|---------|
| Instagram | 1080x1350 (4:5) | 8 MB | JPG, PNG |
| TikTok | 1080x1920 (9:16) | — | JPG, JPEG, WEBP |
| Facebook | 1200x630 | 10 MB | JPG, PNG, GIF, WebP |
| LinkedIn | 1200x627 | 8 MB | JPG, PNG, GIF |
| Pinterest | 1000x1500 (2:3) | 20 MB | JPG, PNG, GIF, WEBP |
| Threads | 1440px max width | 8 MB | JPG, PNG |
| Bluesky | — | 1 MB per image | JPG, PNG, GIF, WEBP |
| Reddit | unavailable | — | — |

See [Photo Requirements](/api/photo-requirements) for detailed specifications.

### How do I add automatic music to TikTok photos?

Use the `auto_add_music` parameter:

```bash
curl -X POST https://api.upload-post.com/api/upload_photos \
  -H 'Authorization: Apikey YOUR_API_KEY' \
  -F 'photos[]=@photo1.jpg' \
  -F 'photos[]=@photo2.jpg' \
  -F 'title="Photo slideshow with music"' \
  -F 'user="myprofile"' \
  -F 'platform[]=tiktok' \
  -F 'auto_add_music=true'
```

TikTok will automatically add background music to your photo slideshow.

---

## Scheduling

### How do I schedule a post for later?

Add the `scheduled_date` parameter (ISO-8601 format) to any upload request:

```bash
curl -X POST https://api.upload-post.com/api/upload \
  -H 'Authorization: Apikey YOUR_API_KEY' \
  -F 'video=@video.mp4' \
  -F 'title="Scheduled Post"' \
  -F 'user="myprofile"' \
  -F 'platform[]=instagram' \
  -F 'scheduled_date="2024-12-31T23:45:00Z"'
```

The API returns a `job_id` that you can use to check status, edit, or cancel the scheduled post.

**Constraints**:
- Must be in the future
- Maximum 365 days ahead

### What timezone does the API use?

By default, the API uses **UTC**. To use a different timezone, add the `timezone` parameter:

```bash
-F 'scheduled_date="2024-12-31T20:00:00"' \
-F 'timezone="America/New_York"'
```

Use any valid [IANA timezone identifier](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones) (e.g., `Europe/Madrid`, `America/Los_Angeles`, `Asia/Tokyo`).

### How do I check the status of a scheduled post?

Use the Upload Status endpoint with the `job_id`:

```bash
curl 'https://api.upload-post.com/api/uploadposts/status?job_id=YOUR_JOB_ID' \
  -H 'Authorization: Apikey YOUR_API_KEY'
```

To list all scheduled posts:
```bash
curl 'https://api.upload-post.com/api/uploadposts/schedule' \
  -H 'Authorization: Apikey YOUR_API_KEY'
```

To cancel a scheduled post:
```bash
curl -X DELETE 'https://api.upload-post.com/api/uploadposts/schedule/JOB_ID' \
  -H 'Authorization: Apikey YOUR_API_KEY'
```

See [Manage Scheduled Posts](/api/schedule-posts) for full details.

---

## n8n Integration

### How do I configure credentials in n8n?

1. Add an **HTTP Request Node** to your workflow
2. Set **Method** to `POST`
3. Set **URL** to `https://api.upload-post.com/api/upload`
4. Under **Headers**, add:
   - Name: `Authorization`
   - Value: `Apikey YOUR_API_KEY`
5. Set **Body** to `multipart/form-data`
6. Add required parameters: `user`, `title`, `platform[]`

**Security tip**: Use n8n's Credentials store instead of hardcoding your API key.

### How do I pass binary files in n8n?

In the HTTP Request node, use the `formBinaryData` option:

```json
{
  "formBinaryData": {
    "video": "={{$binary.data}}"
  }
}
```

This passes the binary data from a previous node (like a Google Drive trigger) as the video file.

**Alternative**: You can pass a video URL instead of binary data:
```json
{
  "bodyParameters": {
    "parameters": [
      { "name": "video", "value": "https://example.com/video.mp4" }
    ]
  }
}
```

### Where do I find the Upload-Post n8n community node?

Currently, Upload-Post integration is done via the standard **HTTP Request node**. We provide:

- [Complete JSON node configuration](/guides/n8n-integration#complete-json-node-configuration)
- [Example workflow template on n8n.io](https://n8n.io/workflows/2894-upload-to-instagram-tiktok-and-youtube-from-google-drive/)

See our [n8n Integration Guide](/guides/n8n-integration) for step-by-step setup instructions.

---

## Common Errors

### Error: "Username required in form data"

The `user` parameter is missing from your request. This field identifies which Upload-Post profile to use.

```bash
# Correct
-F 'user="your_profile_username"'
```

### Error: "Video URL is not accessible"

The provided video URL cannot be fetched. Check that:

1. The URL is publicly accessible (test in incognito browser)
2. The URL points directly to a video file (not a webpage)
3. For Google Drive: sharing is set to "Anyone with the link"
4. The file exists and isn't too small (< 1KB indicates broken link)

### Error: "TikTok photo upload timed out"

TikTok photo uploads can be slow. Try:

1. Use `async_upload=true` to avoid timeout
2. Reduce image file sizes
3. Upload fewer photos at once
4. Check TikTok service status

### Error: "File name too long"

Some platforms reject files with very long names. Rename your file to something shorter (under 100 characters) before uploading.

### Error 502/504 Gateway Timeout

These indicate server-side timeouts, usually for large files or slow platform responses.

**Solutions**:
1. Use `async_upload=true` for large uploads
2. Reduce file size if possible
3. Retry the request
4. If persistent, contact support

### Error: "Your [Platform] session has expired"

Your social account connection has expired. Go to [Manage Users](https://app.upload-post.com/manage-users), disconnect the account, and reconnect it.

See [Common Errors](/resources/common-errors) for a complete error reference.

---

## Platform-Specific Features

### Can I add a first comment automatically?

Yes! Use the `first_comment` parameter:

```bash
-F 'first_comment="Check out the link in bio!"'
```

Supported on: **Instagram, Facebook, Threads, Bluesky, Reddit, X, YouTube, LinkedIn, and TikTok**.

For platform-specific first comments, use `[platform]_first_comment`:
```bash
-F 'instagram_first_comment="Follow for more! #photography"'
-F 'youtube_first_comment="Subscribe for more videos!"'
-F 'tiktok_first_comment="Full tutorial in the link in bio."'
```

On **TikTok** it works on video and photo posts, but the account must have been reconnected recently — the connection needs the `comments` [capability](/api/user-profiles#account-capabilities). If it does not have it, the post is published anyway and the response carries a `warnings` entry saying the first comment was skipped; the first comment never fails the publish. It is also skipped on a draft, because there is nothing published to comment on yet.

### How do I upload Stories to Instagram/Facebook?

Use the `media_type` or `facebook_media_type` parameter set to `"STORIES"`:

**Instagram Stories (video)**:
```bash
-F 'platform[]=instagram' \
-F 'media_type="STORIES"'
```

**Instagram Stories (photo)**:
```bash
-F 'platform[]=instagram' \
-F 'media_type="STORIES"'
```

**Facebook Stories**:
```bash
-F 'platform[]=facebook' \
-F 'facebook_media_type="STORIES"'
```

### Can I add custom thumbnails?

Yes, for **YouTube** (standard videos only, not Shorts):

```bash
# Via URL
-F 'thumbnail_url="https://example.com/thumbnail.jpg"'

# Via file upload
-F 'thumbnail=@thumbnail.jpg;type=image/jpeg'
```

**Requirements**: JPG/PNG/GIF/BMP, max 2 MB.

### Can I add a custom cover image for Instagram Reels?

Yes, for **Instagram Reels** you can provide a cover image via URL or file upload:

```bash
# Via URL
-F 'cover_url="https://example.com/cover.jpg"'

# Via file upload (binary)
-F 'cover_image=@cover.jpg;type=image/jpeg'
```

**Requirements**: JPEG format, max 8 MB, recommended aspect ratio 9:16.

### How do I mark content as AI-generated?

For **TikTok**, use the `is_aigc` parameter:
```bash
-F 'is_aigc=true'
```

For **YouTube**, use `containsSyntheticMedia`:
```bash
-F 'containsSyntheticMedia=true'
```

### Is there an API to read/respond to comments?

Yes. The [Comments API](/api/comments) lists, creates and deletes comments on Instagram, Facebook, YouTube, LinkedIn and **TikTok** with a single endpoint per operation. On TikTok the same endpoints also [list the replies under a comment](/api/comments#list-comments) (add `comment_id`) and [hide, like or pin one](/api/comments#hide-like-or-pin-a-comment).

TikTok comments need an account that was **reconnected** recently — the connection must report the `comments` [capability](/api/user-profiles#account-capabilities). Reconnect it from [Manage Users](https://app.upload-post.com/manage-users); until then those calls answer `400` with `error_code: "tiktok_reconnect_required"`.

For Instagram there are also [private and public replies](/api/instagram-comments), [DMs](/api/instagram-dms) and [AutoDMs](/api/autodms).

---

## White-Label Integration

### How does white-label integration work?

White-label allows you to integrate Upload-Post into your own platform, so your users can connect their social accounts through your interface:

1. **Create profiles** for your users via API
2. **Generate secure URLs** (JWT) for account linking
3. **Users connect** their accounts through the Upload-Post interface (customizable branding)
4. **You manage** their content via API using their profile username

See the [White-label Integration Guide](/guides/user-profile-integration) for implementation details.

### How do I generate connection URLs for my users?

```bash
curl -X POST https://api.upload-post.com/api/uploadposts/users/generate-jwt \
  -H "Authorization: Apikey YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "your_user_id_123",
    "redirect_url": "https://yourapp.com/connected",
    "logo_image": "https://yourapp.com/logo.png",
    "connect_title": "Connect Your Social Accounts",
    "platforms": ["instagram", "tiktok", "facebook"]
  }'
```

The response includes an `access_url` valid for 48 hours. Redirect your user to this URL.

### Can I use my own domain for the connection page?

Currently, the connection page is hosted at `app.upload-post.com`. You can customize:

- **Logo**: via `logo_image` parameter
- **Title**: via `connect_title` parameter
- **Description**: via `connect_description` parameter
- **Button text**: via `redirect_button_text` parameter
- **Redirect URL**: via `redirect_url` parameter
- **Visible platforms**: via `platforms` array
- **Calendar visibility**: via `show_calendar` parameter

Custom domain support is not currently available.

---

## Billing, Invoices & Payments

### How do I access my invoices?

You can access all your invoices through the **Stripe Billing Portal**:

1. Log in to your [Upload-Post Dashboard](https://app.upload-post.com/)
2. Go to **My Profile**
3. Click the **"Open Billing Portal"** button in the Invoices & Billing card at the top
4. In the Stripe Portal, click **"Invoice History"** to view and download all invoices

You can also access the Billing Portal from the profile dropdown menu in the navigation bar on any page.

### How do I update my payment method?

1. Go to **My Profile** in the dashboard
2. Click **"Open Billing Portal"**
3. In the Stripe Portal, click **"Payment methods"**
4. Add a new card or update your existing payment method

### What are the plan prices?

Visit our [Pricing page](https://www.upload-post.com/pricing) for current prices. We offer:

- **Basic** - For individuals getting started
- **Professional** - For power users and small teams
- **Advanced** - For agencies and larger teams
- **Business** - For enterprise with usage-based billing

All plans are available with monthly or yearly billing. Yearly plans include a significant discount.

### Do you charge VAT/IVA/taxes?

Upload-Post uses **Stripe** for payment processing. Depending on your country and tax regulations:

- **EU customers**: VAT may be applied automatically based on your billing address. Stripe handles EU VAT compliance.
- **Non-EU customers**: Taxes depend on your local regulations. Stripe may collect applicable taxes based on your billing address.

Your invoices in the Stripe Billing Portal will show any applied taxes. If you need a tax ID (VAT number) added to your invoices, you can add it directly in the Stripe Portal under your billing details.

### How do I cancel my subscription?

To cancel your subscription:

1. Log in to your [Upload-Post Dashboard](https://app.upload-post.com/)
2. Go to **My Profile**
3. Click **"Cancel Subscription"** and follow the steps

Your access continues until the end of the current billing period. You can also cancel directly from the Stripe Billing Portal.

### How do I request a refund?

Contact our support team at **info@upload-post.com** with:

- Your account email
- Reason for the refund request
- Any relevant details

Refund eligibility depends on your subscription terms and usage.

### Can I pause my subscription instead of canceling?

Yes! When you click "Cancel Subscription", you'll be offered the option to **pause** your subscription for 1 or 3 months instead. During the pause:

- Your data and connected accounts are preserved
- No charges during the pause period
- You can resume at any time from your profile

### How do I change my account email?

You can change your email directly from the dashboard:

1. Go to **My Profile**
2. Click **"Change email"** next to your current email
3. Enter your new email address
4. Click **"Send Verification"**
5. Check your **current email** inbox and click the confirmation link
6. Then check your **new email** inbox and click the second confirmation link
7. Your email will be updated and you'll need to log in again

This double-confirmation process protects your account from unauthorized changes.

### How do I contact human support?

- **Email**: info@upload-post.com
- **Twitter/X**: [@vcaverog](https://x.com/vcaverog)
- **GitHub**: [github.com/upload-post](https://github.com/upload-post)

When contacting support, please include:
- Your API key (masked)
- Exact error messages
- Steps to reproduce the issue
- Platform(s) affected

### Can I buy additional profiles?

Yes! You can add extra profiles as add-ons to your existing subscription:

1. Go to **My Profile** in the dashboard
2. Under Subscription, click **"Manage Extra Profiles"**
3. Choose the add-on size that fits your needs

Alternatively, you can upgrade to a higher tier plan at [upload-post.com/pricing](https://www.upload-post.com/pricing) for more base profiles.

### What happens to my data if I cancel?

When you cancel your subscription:

- Your data and connected accounts remain until the end of the billing period
- After the billing period ends, your account reverts to the free plan (limited uploads)
- Your connected social accounts and profile data are preserved
- You can resubscribe at any time to restore full access

---

## Still Have Questions?

If your question isn't answered here:

1. Check our detailed [API Reference](/api/reference)
2. Review the [Common Errors](/resources/common-errors) guide
3. Contact support at **info@upload-post.com**


---
# Pricing & Limits
URL: https://docs.upload-post.com/resources/pricing-and-limits

# Pricing & Limits

Concrete numbers for every Upload-Post plan and every enforced limit, in one place. Prices below are in USD; the live source of truth (including EUR prices and the annual-billing toggle) is [upload-post.com/#pricing](https://www.upload-post.com/#pricing).

## Plans

All paid plans include **unlimited uploads** (unlimited API upload calls per month), all supported platforms, scheduling and analytics. "Profiles" are Upload-Post sub-accounts: each profile can connect one account per platform (5 profiles = up to 5 TikTok accounts, 5 Instagram accounts, and so on).

| | Free | Basic | Professional | Advanced | Business |
|---|---|---|---|---|---|
| **Price (monthly billing)** | $0 | $24/mo | $50/mo | $147/mo | $438/mo |
| **Price (annual billing)** | $0 | $16/mo ($192/yr) | $33/mo ($400/yr) | $118/mo ($1,411/yr) | $350/mo ($4,205/yr) |
| **Uploads per month** | 10 | Unlimited | Unlimited | Unlimited | Unlimited |
| **Profiles** | 2 | 5 | 25 | 75 | 225 |
| **TikTok posting** | No | Yes | Yes | Yes | Yes |
| **Whitelabel integration** | No | No | Yes | Yes | Yes |
| **Priority support** | No | No | No | Yes | Yes |
| **Team seats** | 1 | 1 (owner only) | 2 | 5 | 10 |
| **FFmpeg video editor API** | 30 min/mo | 300 min/mo | 1,000 min/mo | 3,000 min/mo | 10,000 min/mo |
| **AI Shorts Uploader** | 10 analyses/mo | 100 analyses/mo | 300 analyses/mo | 600 analyses/mo | 1,000 analyses/mo |

Annual billing is ~40% cheaper than monthly. The Free plan requires no credit card. Check your current plan and usage with [`GET /api/uploadposts/me`](../api/current-user.md).

### Add-ons

- **Extra profiles** (choose one size, they don't stack): Basic +5 ($120/yr) or +10 ($200/yr); Professional +15 ($280/yr) or +25 ($440/yr); Advanced +25 ($650/yr) or +50 ($1,150/yr); Business: +$1/mo per extra profile.
- **[X Links add-on](../guides/x-links-addon.md):** +$19/mo. Keeps clickable URLs in X (Twitter) posts, up to 50 link-posts/mo (URLs are [stripped by default](../resources/character-limits.md#x-twitter-character-limits) to avoid X's 13× per-URL fee).

## Upload limits per plan

- **Free:** 10 uploads per month; exceeding it returns `429` with your current `usage`. TikTok uploads return `403` (paid plans only).
- **Paid plans:** unlimited monthly API upload calls. You're still subject to the per-platform daily caps below, your plan's profile count, and each network's own rules.

## Daily platform caps (per connected account, rolling 24 h)

Upload-Post enforces hard caps per connected social account to protect accounts from platform bans. They are **per account**, not per API key: 5 profiles with 5 TikTok accounts get 5 × 15 = 75 TikTok posts/day. Full details in [Limit of uploads](../guides/limit-of-uploads.md).

| Platform | Hard cap (posts / 24 h) |
|----------|------------------------:|
| Instagram | 50 |
| TikTok | 15 |
| LinkedIn | 150 |
| YouTube | 10 |
| Facebook | 25 |
| X (Twitter) | Per plan: Free/Basic 10 · Professional 20 · Advanced/Business 30 |
| Threads | 50 |
| Pinterest | 20 |
| Reddit | unavailable (`reddit_unavailable`) |
| Bluesky | 25 videos / 10 GB (video quota) |

Hitting a cap returns `429 Too Many Requests` with a `violations` array. Scheduled posts re-check the cap at execution time.

## API rate limits

From the [Rate Limits guide](../guides/rate-limits.md):

- Every authenticated response carries `X-RateLimit-Limit`, `X-RateLimit-Remaining` and `X-RateLimit-Reset` headers; exceeding the window returns `429`.
- **Duplicate protection:** identical uploads (same user, platform, content hash) within a short window return the existing request; send an `Idempotency-Key` header for exactly-once retries.
- **Brute-force protection:** 10 consecutive failed auth attempts block the IP for 5 minutes.
- **Polling:** don't poll [Upload Status](../api/upload-status.md) faster than every 5 s, since statuses are cached for 2 to 5 s ([recommended intervals](../guides/rate-limits.md#recommended-polling-intervals)). Or skip polling and use [webhooks](../api/webhooks.md).

## Other concrete limits worth knowing

- **Sync uploads switch to async after 59 s.** Use [`async_upload=true`](../guides/async-uploads.md) and poll with the returned `request_id`.
- **Scheduling:** `scheduled_date` must be in the future, at most 365 days ahead ([Schedule posts](../api/schedule-posts.md)).
- **White-label JWT connect URLs** expire after 48 hours ([User Profiles API](../api/user-profiles.md)).
- **AutoDM monitors:** 2 per profile per day, auto-expire after 15 days ([AutoDM API](../api/autodms.md)).
- **Per-platform media caps** (file size, duration, carousel items): see [Video Requirements](../api/video-requirements.md), [Photo Requirements](../api/photo-requirements.md) and [Character Limits](./character-limits.md).


---
# Support
URL: https://docs.upload-post.com/resources/support

# Support

We're here to help you succeed with the Upload-Post API. Here are the different ways you can get support:

## Contact Options

### Email Support
- **Support**: info@upload-post.com

### Community Support
- Follow us on [Twitter](https://x.com/vcaverog)
- Check our [GitHub repository](https://github.com/upload-post)

## Getting Help

### Before Contacting Support
1. Check our [documentation](../introduction)
2. Review our [FAQ](./faq)
3. Search for similar issues in our [GitHub issues](https://github.com/upload-post)

### When Contacting Support
Please include:
- Your API key (masked)
- Error messages or logs
- Steps to reproduce the issue
- Expected vs actual behavior
- Any relevant code snippets

## Bug Reports

If you've found a bug:
1. Check if it's already reported on [GitHub](https://github.com/upload-post)
2. Create a new issue with:
   - Clear description
   - Steps to reproduce
   - Expected behavior
   - Actual behavior
   - Environment details

## Feature Requests

We welcome feature requests! Submit them through:
- [GitHub issues](https://github.com/upload-post)
- Email to info@upload-post.com


---
# SDK Examples
URL: https://docs.upload-post.com/sdk-examples

# SDK Examples

Explore real-world examples using the Upload Post SDK in Python and JavaScript.

[![PyPI version](https://badge.fury.io/py/upload-post.svg)](https://badge.fury.io/py/upload-post)
[![npm version](https://badge.fury.io/js/upload-post.svg)](https://badge.fury.io/js/upload-post)

## cURL

### Upload Video

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'video=@/path/to/your/video.mp4' \
  -F 'title="Your Video Title"' \
  -F 'user="test"' \
  -F 'platform[]=tiktok' \
  -X POST https://api.upload-post.com/api/upload
```

### Upload Photos

```bash
curl \
  -H 'Authorization: Apikey your-api-key-here' \
  -F 'photos[]=@/path/to/your/image1.jpg' \
  -F 'user="test"' \
  -F 'platform[]=instagram' \
  -F 'title="My Photo Title"' \
  -F 'description="My photo description"' \
  -X POST https://api.upload-post.com/api/upload
```

## Python

### Basic Upload

```python
from upload_post import UploadPostClient

client = UploadPostClient(api_key="your-api-key-here")

# Upload video to multiple platforms
response = client.upload_video(
    video_path="/path/to/video.mp4",
    title="My Awesome Video",
    user="testuser",
    platforms=["tiktok", "instagram"]
)

print('Upload successful:', response)
```

## JavaScript/Node.js

### Basic Upload

```javascript

const uploader = new UploadPost('your-api-key-here');

// Upload video with options
const result = await uploader.upload('/path/to/video.mp4', {
  title: 'My Awesome Video',
  user: 'test-user',
  platforms: ['tiktok'] // Currently supported platforms
});

console.log('Upload successful:', result);
```
