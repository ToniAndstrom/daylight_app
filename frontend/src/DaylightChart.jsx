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
import { Chart } from 'react-chartjs-2';
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

const DaylightChart = ({
  daylightChanges,
  cityName,
  daylightChanges2,
  cityName2,
}) => {
  // Convert daylightChanges to the format needed by Chart.js
  const labels = Object.keys(daylightChanges.daylightChanges);
  const data = Object.values(daylightChanges.daylightChanges).map((duration) =>
    convertToMinutes(duration)
  );
  const data2 = daylightChanges2
    ? Object.values(daylightChanges2.daylightChanges).map((duration) =>
        convertToMinutes(duration)
      )
    : [];

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
      {
        label: `${cityName2} Daylight Changes`,
        data: data2,
        backgroundColor: 'rgba(255, 99, 132, 0.2)',
        borderColor: 'rgba(255, 99, 132, 1)',
        borderWidth: 1,
      },
    ],
  };

  return <Chart type="line" data={chartData} />;
};

// Helper function to convert duration string to total minutes
const convertToMinutes = (duration) => {
  if (typeof duration === 'string' && duration.includes(' hours ')) {
    const parts = duration.split(' ');
    const hours = parseInt(parts[0], 10);
    const minutes = parseInt(parts[2], 10);
    return hours * 60 + minutes;
  }
  return 0;
};

DaylightChart.propTypes = {
  daylightChanges: PropTypes.object.isRequired,
  cityName: PropTypes.string.isRequired,
  daylightChanges2: PropTypes.object,
  cityName2: PropTypes.string,
};

export default DaylightChart;
