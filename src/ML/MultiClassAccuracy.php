<?php
declare(strict_types=1);
namespace CrazyGoat\Forex\ML;

use CrazyGoat\Forex\Service\StringCounter;
use Rubix\ML\CrossValidation\Metrics\Accuracy;

class MultiClassAccuracy extends Accuracy
{
    public function scoreMulti(array $predictions, array $labels): array
    {
        $result = [];
        $uniqueLabels = array_unique($labels);

        foreach ($uniqueLabels as $uniqueLabel) {
            $result[$uniqueLabel] = 0.0;
        }

        $labelsCounter = new StringCounter();
        $predictionCounter = new StringCounter();
        foreach ($labels as $label) {
            $labelsCounter->add($label);
        }

        if (empty($predictions)) {
            return $result;
        }

        foreach ($predictions as $i => $prediction) {
            if ($prediction == $labels[$i]) {
                //var_dump($prediction);
                $predictionCounter->add($prediction);
            }
        }

        //var_dump($predictionCounter, $labelsCounter);

        foreach ($uniqueLabels as $uniqueLabel) {
            $result[$uniqueLabel] = (float) $predictionCounter->count($uniqueLabel) / (float) $labelsCounter->count($uniqueLabel);
        }

        return $result;
    }
}