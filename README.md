# WordPress Image Metadata Generator

**Automate WordPress accessibility with the power of AI. Instantly generate "answer engine" and SEO-friendly alt text, titles, and image descriptions directly in the Media Library using Google's latest Gemini Flash model. A free API key is available [here](https://aistudio.google.com/app/apikey).**

### **Project Architect & Engineer:** [Andrew Hickman](https://andrewhickman.me/)

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)

## 🚀 Overview

[**Fourth Coast Web**](https://fourthcoastweb.com) brings the power of Google's [Gemini](https://deepmind.google/models/gemini/flash/) AI directly into your WordPress dashboard. Stop wasting precious time writing descriptive alternative text, titles, and descriptions for every image. With a single click, the plugin analyzes your uploaded image and automatically populates the **Alt Text**, **Title**, and **Description** fields, ensuring your site is accessible (WCAG compliant) and optimized for search as well as answer engines.

![A preview of the user interface for the Crest AI Image Alt Text Generator by Fourth Coast Web](https://fourthcoastweb.com/wp-content/uploads/2026/01/metadata-generator-fourth-coast-web-preview.webp)
![An animated demo of a user generating alt text with the plugin by Fourth Coast Web](https://fourthcoastweb.com/wp-content/uploads/2026/01/alt-generator-plugin-demo.gif)

## ✨ Features

- **⚡ Instant Generation:** Uses the `gemini-flash-latest` model for fast, accurate image analysis.
- **🖼️ Native Integration:** Seamlessly extends the default WordPress Media Library modal. No navigating away to separate settings pages for daily use.
- **🧠 Context-Aware:** Includes a "Keywords" field, allowing you to guide the AI (e.g., "sunset", "product shot", "marketing") for more relevant descriptions.
- **🔒 Secure:** Your API key is stored securely using WordPress best practices and never exposed to the frontend.
- **♿ Accessibility First:** Generates concise alt text for screen readers and detailed visual descriptions for better context.

## 🛠️ Installation & Configuration

### Prerequisites

- WordPress 6.0 or higher.
- PHP 7.4 or higher.
- A **Google Gemini API Key** (Google provides a generous free tier, no credit card needed. Get an API key [here](https://aistudio.google.com/app/apikey)).

### Setup Steps

#### Option A: Traditional Installation

1.  **Download & Install:**
    - Upload the `media-meta-generator` folder to your `/wp-content/plugins/` directory.
    - Activate the plugin through the 'Plugins' menu in the WordPress admin.

#### Option B: Composer Installation (Bedrock/Roots)

1.  Add the repository to your `composer.json`:
    ```json
    "repositories": [
      {
        "type": "vcs",
        "url": "https://github.com/FourthCoastWeb/Image-Alt-Generator"
      }
    ]
    ```
2.  Require the plugin:
    ```bash
    composer require fourthcoastweb/image-alt-generator/media-meta-generator
    ```

### Configuration

1.  **Configure API Key:**
    - Navigate to **Tools > Media Metadata Generator** in your WordPress admin dashboard.
    - Paste your Google Gemini API Key into the field.
    - Click **Test Connection** to verify your key works.
    - Click **Save Changes**.

## 📖 How to Use

1.  Open the **Media Library** (or click "Add Media" while editing a post).
2.  Select an image to view its details.
3.  Locate the new **Image Metadata Generator** section in the sidebar.
4.  _(Optional)_ Enter any **Keywords** to help the AI understand specific details (e.g., brand names, specific locations), and select which output fields those keywords should appear in.
5.  Click **Generate Alt, Title, and Description**.
6.  Wait a moment for the AI to analyze the image. The **Alternative Text**, **Title**, and **Description** fields will automatically populate.

## 🏗️ Architecture

Fourth Coast Web's **Media Metadata Generator** is built with modern WordPress development standards:

- **Backend:** PHP 7.4+ with strict typing and Namespacing (`Media_Meta_Generator`).
- **Frontend:** Extends the `wp.media.view` (Backbone.js) to inject UI elements natively into the media modal.
- **Security:** Implements robust nonce verification for AJAX requests and sanitizes all inputs.

## 🤝 Contributing

We welcome contributions! Please read our [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, and the process for submitting pull requests.

1.  Fork the repository.
2.  Create your feature branch (`git checkout -b feature/AwesomeFeature`).
3.  Commit your changes (`git commit -m 'Add some AwesomeFeature'`).
4.  Push to the branch (`git push origin feature/AwesomeFeature`).
5.  Open a Pull Request.

## 🛡️ Security

If you discover a security vulnerability within this project, please consult [SECURITY.md](SECURITY.md) for our disclosure policy.

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

---

**Principal Engineer:** [Andrew Hickman](https://andrewhickman.me/)  
**Author:** [Fourth Coast Web](https://fourthcoastweb.com)
