package br.com.vitrineaipro.assistiva.shared

data class AacEvent(
    val id: String,
    val occurredAtEpochMs: Long,
    val symbolId: String,
    val phrase: String?,
    val contextZone: String?
)

data class WearWindow(
    val startedAtEpochMs: Long,
    val endedAtEpochMs: Long,
    val heartRateMean: Double?,
    val heartRateMin: Double?,
    val heartRateMax: Double?,
    val motionScore: Double?,
    val anomalyScore: Double?,
    val signalQuality: String
)

data class EnvironmentWindow(
    val startedAtEpochMs: Long,
    val endedAtEpochMs: Long,
    val noiseLevel: Double?,
    val lightLevel: Double?,
    val temperatureC: Double?,
    val locationZone: String?
)

data class MedicationEvent(
    val medicationName: String,
    val doseValue: Double,
    val doseUnit: String,
    val scheduledAtEpochMs: Long?,
    val administeredAtEpochMs: Long,
    val source: String = "guardian"
)

data class SupportOutcome(
    val occurredAtEpochMs: Long,
    val strategy: String,
    val outcome: String,
    val notes: String? = null
)

enum class SupportState {
    HABITUAL,
    CHANGE_DETECTED,
    MAY_NEED_SUPPORT
}
