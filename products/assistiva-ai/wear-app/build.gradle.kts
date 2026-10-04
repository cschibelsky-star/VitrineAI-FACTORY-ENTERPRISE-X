plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.plugin.compose")
}
android {
    namespace = "br.com.vitrineaipro.assistiva.wear"
    compileSdk = 37
    defaultConfig {
        applicationId = "br.com.vitrineaipro.assistiva"
        minSdk = 30
        targetSdk = 37
        versionCode = 1
        versionName = "0.1.0"
    }
    buildFeatures { compose = true }
}
dependencies {
    implementation(project(":shared"))
    implementation("com.google.android.gms:play-services-wearable:20.0.1")
    implementation("androidx.health:health-services-client:1.1.0")
    implementation("androidx.activity:activity-compose:1.11.0")
    implementation(platform("androidx.compose:compose-bom:2026.09.00"))
    implementation("androidx.wear.compose:compose-material3:1.5.0")
}
