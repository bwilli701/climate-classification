<?php

/*
  Author: Brandon Williams
  Description:
    A script to determine Koeppen climate classification
  based on 37 user inputs in form. Eventually will
  add functionality to allow user to add / retrieve
  records to / from a climatological database.

  To do:
    1. Still need to distuingish subtypes of C and D type climates.
    2. Still need to validate inputs.
    3. Still need to sanitize inputs in anticipation of eventual connection
       to climatological database.
    3. Should probably also give user the option of entering / retrieving data
       in imperial units as well as metric.
*/

  echo "Climate Classification:<br><br>";

  //Variable assignment
  $hemisphere = $_POST["hemisphere"];

  //Arrays
  $high = array($_POST["highJan"], $_POST["highFeb"], $_POST["highMar"],
                $_POST["highApr"], $_POST["highMay"], $_POST["highJun"],
                $_POST["highJul"], $_POST["highAug"], $_POST["highSep"],
                $_POST["highOct"], $_POST["highNov"], $_POST["highDec"]);

  $low = array($_POST["lowJan"], $_POST["lowFeb"], $_POST["lowMar"],
               $_POST["lowApr"], $_POST["lowMay"], $_POST["lowJun"],
               $_POST["lowJul"], $_POST["lowAug"], $_POST["lowSep"],
               $_POST["lowOct"], $_POST["lowNov"], $_POST["lowDec"]);

  $precip = array($_POST["precipJan"], $_POST["precipFeb"], $_POST["precipMar"],
                  $_POST["precipApr"], $_POST["precipMay"], $_POST["precipJun"],
                  $_POST["precipJul"], $_POST["precipAug"], $_POST["precipSep"],
                  $_POST["precipOct"], $_POST["precipNov"], $_POST["precipDec"]);

  function DetermineClassification($hemisphere, $high, $low, $precip)
  {

      function CalculateAvgMonthlyTemps($high, $low)
      {
        $avg = array();

        for ($i = 0; $i < 12; $i++)
        {
          $avg[$i] = ($high[$i] + $low[$i]) / 2;
        }

        return $avg;
      }

      function DetermineLowestAvgTemp($avg)
      {
        //Local variable
        $lowestAvgTemp = $avg[0];

        for ($i = 1; $i < 12; $i++)
        {
          if ($avg[$i] < $lowestAvgTemp)
          {
            $lowestAvgTemp = $avg[$i];
          }
        }

        return $lowestAvgTemp;
      }

      function CalculateAvgAnnualTemp($avg)
      {
        $avgAnnualTemp = 0;

        foreach ($avg as $value)
        {
          $avgAnnualTemp += $value;
        }

        $avgAnnualTemp /= 12;

        return $avgAnnualTemp;
      }

      function DetermineLowestPrecip($precip)
      {
        $lowestPrecip = $precip[0];

        for ($i = 1; $i < 12; $i++)
        {
          if ($precip[$i] < $lowestPrecip)
          {
            $lowestPrecip = $precip[$i];
          }
        }

        return $lowestPrecip;
      }

      function CalculateAvgPrecip($precip)
      {
        $avgPrecip = 0;

        foreach ($precip as $value)
        {
          $avgPrecip += $value;
        }

        return $avgPrecip;
      }

      function CalculatePrecipThreshold($avgAnnualTemp, $avgPrecip,
                                        $hemisphere, $precip)
      {
        //Constants
        define("SEVENTY_PERCENT", $avgPrecip * .7);
        define("THIRTY_PERCENT", $avgPrecip * .3);

        //Local variable
        $precipThreshold = $avgAnnualTemp * 20;

        //Calculate total spring + summer precip
        $springSummerPrecip = CalculateSpringSummerPrecip($hemisphere, $precip);

        //Determine amount to be added to threshold
        if ($springSummerPrecip >= SEVENTY_PERCENT)
        {
          $precipThreshold += 280;
        }
        elseif ($springSummerPrecip < SEVENTY_PERCENT)
        {
          if ($springSummerPrecip >= THIRTY_PERCENT)
          {
            $precipThreshold += 140;
          }
          else
          {
            $precipThreshold += 0;
          }

        }

        return $precipThreshold;
      }

      function CalculateSpringSummerPrecip($hemisphere, $precip)
      {
        //Local variable
        $springSummerPrecip = 0;

        if ($hemisphere == "northern")
        {

          for ($i = 3; $i < 8; $i++)
          {
            $springSummerPrecip += $precip[$i];
          }
        }
        else
        {
          for ($i = 9; $i < 2; $i++)
          {
            $springSummerPrecip += $precip[$i];
          }
        }

        return $springSummerPrecip;
      }

      function TestA($avg)
      {
        //Constant
        define("TROP_THRESHOLD", 18);

        //Local variable
        $tropical = true;

        //Test to determine whether climate is tropical
        foreach ($avg as $value)
        {
          if ($value < TROP_THRESHOLD)
          {
            $tropical = false;
          }
        }

        return $tropical;
      }

      function RefineA($lowestPrecip, $precip, $avgPrecip)
      {
        //Constants
        define("RAINFOREST_THRESHOLD", 60);
        define("MONSOON_THRESHOLD", $avgPrecip * .04);

        //Local variables
        $classification = "A";
        $rainforest = true;
        $monsoon = false;
        $savanna = false;

        //Assign 2nd letter
        foreach ($precip as $value)
        {
          if ($value < RAINFOREST_THRESHOLD)
          {
            $rainforest = false;
          }
        }

        if ($lowestPrecip < RAINFOREST_THRESHOLD)
        {
          if ($lowestPrecip > MONSOON_THRESHOLD)
          {
            $monsoon = true;
          }
          if ($lowestPrecip == MONSOON_THRESHOLD)
          {
            $monsoon = true;
            $savanna = true;
          }

        }

        if ($rainforest == true)
        {
          $classification .= "f";
        }
        elseif (($monsoon == true) && ($savanna == false))
        {
          $classification .= "m";
        }
        elseif (($monsoon == true) && ($savanna == true))
        {
          $classification = "Borderline Am / Aw";
        }
        else
        {
          $classification .= "w";
        }

        return $classification;

      }

      function TestB($avgPrecip, $precipThreshold)
      {
        //Local variable
        $lowPrecip = false;

        //
        if ($avgPrecip <= $precipThreshold)
        {
          $lowPrecip = true;
        }

        return $lowPrecip;
      }

      function RefineB($avgPrecip, $precipThreshold, $lowestAvgTemp)
      {
        //Constant
        define("FIFTY_PERCENT", $precipThreshold * .5);
        define("ZERO_ISOTHERM", 0);

        //Local variables
        $classification = "B";
        $temporaryClassification = "";

        //Assign 2nd letter
        if ($avgPrecip < FIFTY_PERCENT)
        {
          $classification .= "W";
        }
        else
        {
          $classification .= "S";
        }

        //Assign 3rd letter
        if ($lowestAvgTemp > ZERO_ISOTHERM)
        {
          $classification .= "h";
        }
        elseif ($lowestAvgTemp == ZERO_ISOTHERM)
        {
          //Borderline BWh/BWk, or BSh/BSk
          $temporaryClassification = $classification;
          $classification = "Borderline " . $temporaryClassification . "h" .
                                    " / " . $temporaryClassification . "k";
        }
        else
        {
          $classification .= "k";
        }

        return $classification;
      }

      function TestC()
      {

      }

      function RefineC()
      {

      }

      function RefineD()
      {

      }

      function TestE($avg)
      {
        //Constant
        define("POLAR_ALPINE_THRESHOLD", 10);

        //Local variable
        $polarAlpine = true;

        //Test to determine whether climate is polar / alpine
        foreach ($avg as $value)
        {
          if ($value >= POLAR_ALPINE_THRESHOLD)
          {
            $polarAlpine = false;
          }
        }

        return $polarAlpine;
      }

      function RefineE($avg)
      {
        //Constant
        define("ZERO_ISOTHERM", 0);

        //Local variables
        $classification = "E";
        $iceCap = true;

        foreach ($avg as $value)
        {
          if ($value >= ZERO_ISOTHERM)
          {
            $iceCap = false;
          }
        }

        if ($iceCap == false)
        {
          $classification .= "T";
        }
        else
        {
          $classification .= "F";
        }

        return $classification;
      }

      //Perform climatological calculations
      $avg = CalculateAvgMonthlyTemps($high, $low);
      $lowestAvgTemp = DetermineLowestAvgTemp($avg);
      $avgAnnualTemp = CalculateAvgAnnualTemp($avg);
      $lowestPrecip = DetermineLowestPrecip($precip);
      $avgPrecip = CalculateAvgPrecip($precip);
      $precipThreshold = CalculatePrecipThreshold($avgAnnualTemp, $avgPrecip,
                                                  $hemisphere, $precip);

      /*
        Plan for selection structure tests:

        If TestE == true, RefineE
        Else, If TestB == true, RefineB
        Else, If TestA == true, RefineA
        Else, If TestC == true, RefineC <-Next test to implement
        Else, RefineD
      */

      //Determine climate
      if (TestE($avg) == true)
      {
        $classification = RefineE($avg);
      }
      elseif (TestB($avgPrecip, $precipThreshold) == true)
      {
        $classification = RefineB($avgPrecip, $precipThreshold, $lowestAvgTemp);
      }
      elseif (TestA($avg) == true)
      {
        $classification = RefineA($lowestPrecip, $precip, $avgPrecip);
      }
      else
      {
        $classification = "UNK";
      }

      echo $classification;
  }

  DetermineClassification($hemisphere, $high, $low, $precip);
?>
