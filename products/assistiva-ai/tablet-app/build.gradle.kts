plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.plugin.compose")
}
android {
    namespace = "br.com.vitrineaipro.assistiva.tablet"
    compileSdk = 37
    defaultConfig {
        applicationId = "br.com.vitrineaipro.assistiva"
        minSdk = 30
        targetSdk = 37
        versionCode = 5
        versionName = "0.5.0"
    }
    buildFeatures { compose = true }
}
dependencies {
    implementation("androidx.health.connect:connect-client:1.1.0")
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.10.2")
    implementation(project(":shared"))
    implementation("androidx.activity:activity-compose:1.11.0")
    implementation(platform("androidx.compose:compose-bom:2026.09.00"))
    implementation("androidx.compose.material3:material3")
    implementation("androidx.compose.ui:ui")
    implementation("androidx.compose.ui:ui-tooling-preview")
    debugImplementation("androidx.compose.ui:ui-tooling")
}

