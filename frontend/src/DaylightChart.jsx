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
    const parts = duration.split(' ');
    const hours = parseInt(parts[0]);
    const minutes = parseInt(parts[2]);
    return hours * 60 + minutes; // Convert to total minutes
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
