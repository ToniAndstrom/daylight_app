import React from 'react';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { Chart } from 'react-chartjs-2'; // Corrected import
import PropTypes from 'prop-types';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  Title,
  Tooltip,
  Legend
);

const DaylightChart = ({ daylightChanges, cityName }) => {
  // Convert daylightChanges to the format needed by Chart.js
  const labels = Object.keys(daylightChanges);
  const data = Object.values(daylightChanges).map((duration) => {
    // Check if duration is a string and has the expected format
    if (typeof duration === 'string' && duration.includes(' hours ')) {
      const parts = duration.split(' ');
      const hours = parseInt(parts[0], 10);
      const minutes = parseInt(parts[2], 10);
      return hours * 60 + minutes; // Convert to total minutes
    }
    return 0; // Return a default value if duration is not a string
  });

  const chartData = {
    labels,
    datasets: [
      {
        label: `${cityName} Daylight Changes`,
        data,
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1,
      },
    ],
  };

  return <Chart type="line" data={chartData} />; // Corrected usage
};

DaylightChart.propTypes = {
  daylightChanges: PropTypes.object.isRequired,
  cityName: PropTypes.string.isRequired,
};

export default DaylightChart; // Exporting DaylightChart instead of App
