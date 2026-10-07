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
        versionCode = 8
        versionName = "0.8.0-c26-diagnostic"
    }
    buildFeatures { compose = true }
}
dependencies {
    implementation("androidx.lifecycle:lifecycle-runtime-compose:2.9.4")
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
