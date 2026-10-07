pluginManagement {
    repositories { google(); mavenCentral(); gradlePluginPortal() }
}
dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS)
    repositories {
        google()
        mavenCentral()
        maven {
            url = uri("https://maven.topstepht.com/repository/maven-public/")
            content {
                includeGroup("com.topstep.wearkit")
                includeGroup("com.topstep.opus")
                includeGroup("com.topstep.tool")
            }
        }
    }
}
rootProject.name = "AssistivaAI"
include(":tablet-app", ":wear-app", ":shared")
