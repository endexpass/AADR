<?php
/**
 * Created by PhpStorm.
 * User: Endale
 * Date: 10/29/2025
 * Time: 10:55 PM
 */
package main

import (
    "fmt"
	"math"
)
// Sample Data Structures
type AnimalData struct {
    Species  string
	Symptoms map[string]float64
}

type EnvironmentalData struct {
    Temperature float64
	Humidity    float64
}

type PriorProbabilities map[string]float64

type SymptomProbabilityMatrix map[string]map[string]float64

// Function to Filter Symptoms (Noise Filtering)
func FilterSymptoms(AnimalData AnimalData, Thresholds map[string]map[string][2]float64, NoiseRemovalMethod string) map[string]float64 {
    FilteredData := make(map[string]float64)

	for Symptom, Value := range AnimalData.Symptoms {
        Species := AnimalData.Species
		MinValue, MaxValue := Thresholds[Species][Symptom]

		// Check if value is within thresholds
		if Value < MinValue || Value > MaxValue {
            if NoiseRemovalMethod == "moving_average" {
                FilteredData[Symptom] = ApplyMovingAverage(Value, AnimalData.Symptoms, Symptom)
			} else {
                FilteredData[Symptom] = Value
			}
        } else {
            FilteredData[Symptom] = Value
		}
	}
	return FilteredData
}

// Function to Normalize Symptom Value
func NormalizeSymptomValue(value float64, symptom string, species string, thresholds map[string]map[string][2]float64) float64 {
    if species != "" {
        threshold := thresholds[species][symptom]
		lower, upper := threshold[0], threshold[1]

		if value < lower {
            return 0
		} else if value > upper {
            return 1
		} else {
            return (value - lower) / (upper - lower)
		}
	}
    return value
}

// Function to Apply Environmental Adjustment
func ApplyEnvironmentalAdjustment(normalizedValue float64, environmentalData EnvironmentalData, symptom string) float64 {
    // Example adjustment factors (to be expanded as needed)
    adjustmentFactors := map[string]map[string]float64{
        "Fever":     {"Temperature": 0.1, "Humidity": 0.05},
		"Heart Rate": {"Temperature": 0.2, "Humidity": 0.1},
	}

	if factors, exists := adjustmentFactors[symptom]; exists {
        adjustedValue := normalizedValue
		for factor, weight := range factors {
            switch factor {
                case "Temperature":
                    adjustedValue += weight * (environmentalData.Temperature / 100)
			case "Humidity":
                adjustedValue += weight * (environmentalData.Humidity / 100)
			}
        }
		// Ensure the adjusted value is between 0 and 1
		return math.Max(0, math.Min(1, adjustedValue))
	}
	return normalizedValue
}

// Function to Calculate Disease Likelihood Score
func CalculateDiseaseLikelihoodScore(AnimalData AnimalData, EnvironmentalData EnvironmentalData, SymptomProbabilityMatrix SymptomProbabilityMatrix, PriorProbabilities PriorProbabilities, Thresholds map[string]map[string][2]float64, NoiseRemovalMethod string) string {
    // Step 1: Noise Filtering
    FilteredData := FilterSymptoms(AnimalData, Thresholds, NoiseRemovalMethod)

	// Step 2: Disease Likelihood Calculation
	DiseaseLikelihoodScores := make(map[string]float64)

	for disease, priorProbability := range PriorProbabilities {
        likelihood := 1.0

		for symptom, value := range FilteredData {
            // Normalize and adjust value based on environmental data
            normalizedValue := NormalizeSymptomValue(value, symptom, AnimalData.Species, Thresholds)
			adjustedValue := ApplyEnvironmentalAdjustment(normalizedValue, EnvironmentalData, symptom)

			// Calculate likelihood for the disease
			if prob, exists := SymptomProbabilityMatrix[symptom][disease]; exists {
                likelihood *= math.Pow(prob, adjustedValue)
			}
		}
		// Multiply by prior probability
		DiseaseLikelihoodScores[disease] = likelihood * priorProbability
	}

	// Step 3: Normalize Likelihood Scores
	totalLikelihood := 0.0
	for _, score := range DiseaseLikelihoodScores {
        totalLikelihood += score
	}
	for disease := range DiseaseLikelihoodScores {
        DiseaseLikelihoodScores[disease] /= totalLikelihood
	}

	// Step 4: Predict Disease (Disease with highest likelihood score)
	var predictedDisease string
	maxLikelihood := -1.0
	for disease, score := range DiseaseLikelihoodScores {
        if score > maxLikelihood {
            maxLikelihood = score
			predictedDisease = disease
		}
    }

	// Return the predicted disease
	return predictedDisease
}

func main() {
    // Example usage
animalData := AnimalData{
    Species: "Cow",
		Symptoms: map[string]float64{
        "Fever":     38.0,
			"Heart Rate": 75.0,
		},
	}

	environmentalData := EnvironmentalData{
    Temperature: 30.0,
		Humidity:    80.0,
	}

	thresholds := map[string]map[string][2]float64{
    "Cow": {
        "Fever":     {37.5, 39.0},
			"Heart Rate": {60, 90},
		},
	}

	symptomProbabilityMatrix := SymptomProbabilityMatrix{
    "Fever": {
        "DiseaseA": 0.7,
			"DiseaseB": 0.2,
		},
		"Heart Rate": {
        "DiseaseA": 0.8,
			"DiseaseB": 0.3,
		},
	}

	priorProbabilities := PriorProbabilities{
    "DiseaseA": 0.6,
		"DiseaseB": 0.4,
	}

	// Calculate disease likelihood
	predictedDisease := CalculateDiseaseLikelihoodScore(animalData, environmentalData, symptomProbabilityMatrix, priorProbabilities, thresholds, "moving_average")

	// Output the predicted disease
	fmt.Println("Predicted Disease:", predictedDisease)
}
