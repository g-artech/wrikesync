import React from 'react';
import ReactDom from 'react-dom';
import {AppContainer} from 'react-hot-loader';
import App from './containers/App/App';
import './index.css'

// Enable React devtools
window['React'] = React;

const render = (Component) => {
	ReactDom.render(
		<AppContainer>
			<Component/>
		</AppContainer>,
		document.getElementById('wrikesync-root')
	);
};

$(document).ready(() => {
	render(App);

// Hot Module Replacement API
	if (module.hot) {
		module.hot.accept('./App', () => {
			render(App)
		});
	}
});
