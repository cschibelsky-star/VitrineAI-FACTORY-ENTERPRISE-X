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
        versionCode = 9
        versionName = "0.9.0-fitcloud-test"
    }
    buildFeatures { compose = true }
    packaging {
        jniLibs.pickFirsts.add("**/libc++_shared.so")
        resources.excludes.add("META-INF/INDEX.LIST")
    }
}
dependencies {
    implementation("com.topstep.wearkit:sdk-base:3.0.2.7")
    implementation("com.topstep.wearkit:sdk-fitcloud:3.0.2.7")
    implementation("io.reactivex.rxjava3:rxjava:3.1.5")
    implementation("io.reactivex.rxjava3:rxandroid:3.0.2")
    implementation("com.polidea.rxandroidble3:rxandroidble:1.17.2")
    implementation("com.jakewharton.timber:timber:5.0.1")
    implementation("com.squareup.okhttp3:okhttp:4.10.0")
    implementation("androidx.palette:palette-ktx:1.0.0")
    implementation("androidx.lifecycle:lifecycle-process:2.9.4")
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
