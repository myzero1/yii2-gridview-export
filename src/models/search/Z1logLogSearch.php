<?php

namespace myzero1\log\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use myzero1\log\models\Z1logLog;

/**
 * Z1logLogSearch represents the model behind the search form of `myzero1\log\models\Z1logLog`.
 */
class Z1logLogSearch extends Z1logLog
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'created'], 'integer'],
            [['user_name', 'ip', 'url', 'text', 'screenshot'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Z1logLog::find(); 

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => 30,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'created' => $this->created,
        ]);

        $query->andFilterWhere(['like', 'user_name', $this->user_name])
            ->andFilterWhere(['like', 'ip', $this->ip])
            ->andFilterWhere(['like', 'url', $this->url])
            ->andFilterWhere(['like', 'text', $this->text])
            ->andFilterWhere(['like', 'screenshot', $this->screenshot]);

        return $dataProvider;
    }
}
