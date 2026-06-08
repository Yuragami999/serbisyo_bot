# serbisyo_bot
An AI-powered, multi-tenant conversational assistant designed to bridge the critical information gap between citizens and local government units (LGUs), specifically launched for Marawi City, BARMM, Philippines.

#Key Features

* Localized Context & Dialect Support: Fine-tuned to understand and respond to local inquiries, incorporating Maranao, Filipino, and English language support seamlessly.
* Multi-Tenant Architecture: Built to serve multiple Local Government Units (LGUs) or departments under unified core logic, ensuring isolated data security for each entity.
* Secure Backend Integration: Utilizes an abstracted API layer communicating securely with AI infrastructure (AWS Bedrock) without exposing runtime credentials.
* Citizen-Centric UX: Provides immediate, 24/7 access to information regarding public services, civic documentation requirements, local fees, and community announcements.

#Technical Overview

The ecosystem is designed to run efficiently on lightweight local environments (like XAMPP) while scaling seamlessly to production cloud servers.

* Backend: PHP (Modular API endpoints handling chat context, feedbacks, and routing)
* Database: MariaDB / MySQL (Structured relational schemas for logging user interactions and audit trails)
* AI Engine: Integration via secure server-side webhooks
* Frontend: Vanilla JavaScript, responsive CSS, and native UI components for seamless mobile web browser access.

# Mission & Impact

In rapidly developing administrative landscapes like the Bangsamoro Autonomous Region in Muslim Mindanao (BARMM), access to clear, accurate bureaucratic information can be a bottleneck for everyday citizens. 

SerbisyoBot acts as a decentralized digital concierge. By automating responses to repetitive administrative questions—such as business permit requirements, civil registry steps, and emergency hotlines—the platform reduces long queues at physical government offices, eliminates misinformation, and fosters greater transparency between the Marawi City government and its constituents.
