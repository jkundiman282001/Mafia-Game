# Mafia Game - Deployment Guide

This project is configured for deployment on Vercel using PHP.

## Prerequisites

1.  **Vercel Account**: Sign up at [vercel.com](https://vercel.com).
2.  **Cloud MySQL Database**: Vercel does not host databases. You need a remote MySQL database. Good options include:
    -   [Railway](https://railway.app) (Easy MySQL provisioning)
    -   [PlanetScale](https://planetscale.com)
    -   [Aiven](https://aiven.io)
    -   Any standard hosting (cPanel) that allows remote MySQL access.

## Deployment Steps

1.  **Push to GitHub/GitLab**: Push this code to a repository.
2.  **Import to Vercel**:
    -   Go to Vercel Dashboard -> Add New Project.
    -   Import your repository.
3.  **Environment Variables**:
    -   In the Vercel Project Settings, add the following Environment Variables (matching your Cloud DB credentials):
        -   `DB_SERVER`: (e.g., `containers-us-west-123.railway.app` or `aws.connect.psdb.cloud`)
        -   `DB_USERNAME`: (e.g., `root`)
        -   `DB_PASSWORD`: (your database password)
        -   `DB_NAME`: (e.g., `mafia_game`)

## Database Setup

You must run the SQL commands from `database/setup_database.php` (or `database/database.sql` if you export it) on your **Cloud Database** to create the necessary tables (`users`, `rooms`, `room_players`, `sessions`).

## Session Handling

This project is configured to use **Database Sessions**. This is required for Vercel (serverless) to maintain user logins across requests. The `sessions` table is automatically created if you run the setup script.
